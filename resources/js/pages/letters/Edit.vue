<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, FileDown, RotateCcw, Send, Trash2, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { index as companiesIndex, show } from '@/routes/companies';
import {
    cancel as cancelSend,
    destroy,
    pdf,
    release,
    send,
    update,
} from '@/routes/letters';
import type { Letter, LetterStatus, LetterStatusOption } from '@/types';

const props = defineProps<{
    letter: Letter;
    statuses: LetterStatusOption[];
    duplicateCompanies: string[];
    releasable: boolean;
    cancellable: boolean;
    preview: {
        from: string;
        to: string | null;
        subject: string;
        body: string;
        attachments: string[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Companies',
                href: companiesIndex(),
            },
            {
                title: 'Letter',
                href: companiesIndex(),
            },
        ],
    },
});

const form = useForm({
    subject: props.letter.subject,
    body: props.letter.body,
    email_subject: props.letter.email_subject,
    email_body: props.letter.email_body,
    status: props.letter.status as LetterStatus,
});

const submit = () => {
    form.put(update(props.letter.id).url, { preserveScroll: true });
};

const isSent = computed(() => props.letter.sent_at !== null);
const isSending = computed(() => props.letter.status === 'sending');
const isReady = computed(() => props.letter.status === 'ready');
const isLocked = computed(() => isSent.value || isSending.value);
const recipient = computed(() => props.letter.company?.email ?? null);
const blocked = computed(() => props.letter.company?.do_not_contact === true);

const sendBlockedReason = computed(() => {
    if (isSending.value) {
        return 'This letter is already queued for sending';
    }

    if (blocked.value) {
        return 'This company is marked do not contact';
    }

    if (recipient.value === null) {
        return 'This company has no email address';
    }

    if (!isReady.value) {
        return 'Mark the letter as ready first';
    }

    return null;
});

const formatDateTime = (value: string | null) =>
    value ? new Date(value).toLocaleString() : '-';

const sendOpen = ref(false);
const sending = ref(false);

const scheduleFor = ref('');

const confirmSend = () => {
    router.post(
        send(props.letter.id).url,
        { scheduled_for: scheduleFor.value || null },
        {
            onStart: () => (sending.value = true),
            onFinish: () => (sending.value = false),
            onSuccess: () => (sendOpen.value = false),
        },
    );
};

// The send reads the saved letter, so unsaved edits are not in the preview
// and would not go out either. Saying so beats a preview that quietly
// disagrees with the form above it.
const hasUnsavedChanges = computed(() => form.isDirty);

const cancelling = ref(false);

const cancelSchedule = () => {
    router.post(
        cancelSend(props.letter.id).url,
        {},
        {
            onStart: () => (cancelling.value = true),
            onFinish: () => (cancelling.value = false),
        },
    );
};

const releasing = ref(false);

const releaseLetter = () => {
    router.post(
        release(props.letter.id).url,
        {},
        {
            onStart: () => (releasing.value = true),
            onFinish: () => (releasing.value = false),
        },
    );
};

const deleteOpen = ref(false);
const deleting = ref(false);

const confirmDelete = () => {
    router.delete(destroy(props.letter.id).url, {
        onStart: () => (deleting.value = true),
        onFinish: () => (deleting.value = false),
    });
};
</script>

<template>
    <Head title="Edit letter" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Letter"
                :description="letter.company?.name ?? 'Open-aanbod letter'"
            />
            <div class="flex items-center gap-2">
                <Button variant="outline" as-child>
                    <Link :href="show(letter.company_id)">
                        <ArrowLeft />
                        Back to company
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <a
                        :href="pdf(letter.id).url"
                        target="_blank"
                        rel="noopener"
                    >
                        <FileDown />
                        PDF
                    </a>
                </Button>
                <Button
                    variant="outline"
                    class="text-destructive hover:text-destructive"
                    @click="deleteOpen = true"
                >
                    <Trash2 />
                    Delete
                </Button>
                <Button
                    :disabled="isSent || sendBlockedReason !== null"
                    :title="sendBlockedReason ?? undefined"
                    @click="sendOpen = true"
                >
                    <Send />
                    {{ isSent ? 'Sent' : isSending ? 'Sending' : 'Send' }}
                </Button>
            </div>
        </div>

        <p
            v-if="isSent"
            class="max-w-3xl rounded-md border border-border bg-muted/50 px-3 py-2 text-sm text-muted-foreground"
        >
            This letter was sent and can no longer be edited.
        </p>

        <div
            v-else-if="isSending"
            class="flex max-w-3xl flex-col gap-2 rounded-md border border-border bg-muted/50 px-3 py-2 text-sm text-muted-foreground"
        >
            <template v-if="cancellable">
                <p>
                    Scheduled to send on
                    {{ formatDateTime(letter.scheduled_for) }}. Nothing has been
                    sent yet.
                </p>
                <div>
                    <Button
                        size="sm"
                        variant="outline"
                        :disabled="cancelling"
                        @click="cancelSchedule"
                    >
                        <X />
                        Cancel scheduled send
                    </Button>
                </div>
            </template>
            <p v-else-if="!releasable">
                This letter is queued for sending. Reload in a moment to see the
                result.
            </p>
            <template v-else-if="releasable">
                <p>
                    This letter has been queued for a while without completing.
                    That usually means the worker was restarted mid-send, which
                    leaves nothing to finish the job. No mail was sent.
                </p>
                <div>
                    <Button
                        size="sm"
                        variant="outline"
                        :disabled="releasing"
                        @click="releaseLetter"
                    >
                        <RotateCcw />
                        Release back to ready
                    </Button>
                </div>
            </template>
        </div>

        <p
            v-if="letter.send_error"
            class="max-w-3xl rounded-md border border-destructive/50 px-3 py-2 text-sm text-destructive"
        >
            The last send attempt failed: {{ letter.send_error }}
        </p>

        <form class="max-w-3xl space-y-6" @submit.prevent="submit">
            <fieldset
                :disabled="isLocked"
                class="space-y-6 disabled:opacity-60"
            >
                <div class="grid gap-2">
                    <Label for="subject">Subject</Label>
                    <Input id="subject" v-model="form.subject" required />
                    <InputError :message="form.errors.subject" />
                </div>

                <div class="grid gap-2 sm:max-w-xs">
                    <Label for="status">Status</Label>
                    <Select v-model="form.status">
                        <SelectTrigger id="status" class="w-full">
                            <SelectValue placeholder="Select a status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in statuses"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.status" />
                </div>

                <div class="grid gap-2">
                    <Label for="body">Letter body</Label>
                    <Textarea
                        id="body"
                        v-model="form.body"
                        class="min-h-[420px] font-mono text-sm"
                        required
                    />
                    <InputError :message="form.errors.body" />
                </div>

                <div class="border-t pt-6">
                    <h2 class="text-sm font-medium">Cover email</h2>
                    <p class="text-sm text-muted-foreground">
                        Sent with the letter attached.
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="email_subject">Email subject</Label>
                    <Input
                        id="email_subject"
                        v-model="form.email_subject"
                        required
                    />
                    <InputError :message="form.errors.email_subject" />
                </div>

                <div class="grid gap-2">
                    <Label for="email_body">Email body</Label>
                    <Textarea
                        id="email_body"
                        v-model="form.email_body"
                        class="min-h-[220px] text-sm"
                        required
                    />
                    <InputError :message="form.errors.email_body" />
                </div>

                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="form.processing">
                        Save letter
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="show(letter.company_id)">Cancel</Link>
                    </Button>
                </div>
            </fieldset>
        </form>
    </div>

    <Dialog v-model:open="sendOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Send this letter?</DialogTitle>
                <DialogDescription>
                    This is the message exactly as it will be delivered. The
                    company moves to Sent.
                </DialogDescription>
            </DialogHeader>

            <div
                class="overflow-hidden rounded-md border border-border"
                data-testid="email-preview"
            >
                <dl
                    class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 bg-muted/40 px-3 py-2 text-sm"
                >
                    <dt class="text-xs text-muted-foreground">From</dt>
                    <dd class="font-mono text-xs break-all">
                        {{ preview.from }}
                    </dd>
                    <dt class="text-xs text-muted-foreground">To</dt>
                    <dd class="font-mono text-xs break-all">
                        {{ preview.to ?? 'No email address on this company' }}
                    </dd>
                    <dt class="text-xs text-muted-foreground">Subject</dt>
                    <dd class="text-xs font-medium">{{ preview.subject }}</dd>
                    <dt class="text-xs text-muted-foreground">Attached</dt>
                    <dd class="font-mono text-xs">
                        {{ preview.attachments.join(', ') }}
                    </dd>
                </dl>
                <p
                    class="max-h-64 overflow-y-auto px-3 py-3 text-sm whitespace-pre-line"
                >
                    {{ preview.body }}
                </p>
            </div>

            <p class="text-xs text-muted-foreground">
                <a
                    :href="pdf(letter.id).url"
                    target="_blank"
                    rel="noopener"
                    class="text-primary underline-offset-4 hover:underline"
                >
                    Open the attached letter PDF
                </a>
                to check it before sending.
            </p>

            <p
                v-if="hasUnsavedChanges"
                class="rounded-md border border-amber-500/50 px-3 py-2 text-sm text-amber-700 dark:text-amber-500"
            >
                You have unsaved edits. This preview is the saved letter, and
                that is what will be sent. Cancel and save first if the changes
                should go out.
            </p>

            <div class="grid gap-2">
                <Label for="scheduled_for">Send at (optional)</Label>
                <input
                    id="scheduled_for"
                    v-model="scheduleFor"
                    type="datetime-local"
                    class="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                />
                <p class="text-xs text-muted-foreground">
                    Leave empty to send now. A cold pitch landing late on a
                    Friday is read on Monday, if at all.
                </p>
            </div>

            <p
                v-if="duplicateCompanies.length > 0"
                class="rounded-md border border-destructive/50 px-3 py-2 text-sm text-destructive"
            >
                {{ duplicateCompanies.join(', ') }}
                {{ duplicateCompanies.length === 1 ? 'shares' : 'share' }}
                this email address. That inbox may already have heard from you.
            </p>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancel</Button>
                </DialogClose>
                <Button
                    :disabled="sending || sendBlockedReason !== null"
                    @click="confirmSend"
                >
                    <Send />
                    {{ scheduleFor ? 'Schedule' : 'Send' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="deleteOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete letter?</DialogTitle>
                <DialogDescription>
                    This permanently removes this letter. This cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancel</Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    :disabled="deleting"
                    @click="confirmDelete"
                >
                    Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
