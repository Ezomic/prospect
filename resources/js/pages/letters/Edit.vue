<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, FileDown, Send, Trash2 } from '@lucide/vue';
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
import { destroy, pdf, send, update } from '@/routes/letters';
import type { Letter, LetterStatus, LetterStatusOption } from '@/types';

const props = defineProps<{
    letter: Letter;
    statuses: LetterStatusOption[];
    duplicateCompanies: string[];
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

const sendOpen = ref(false);
const sending = ref(false);

const confirmSend = () => {
    router.post(
        send(props.letter.id).url,
        {},
        {
            onStart: () => (sending.value = true),
            onFinish: () => (sending.value = false),
            onSuccess: () => (sendOpen.value = false),
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

        <p
            v-else-if="isSending"
            class="max-w-3xl rounded-md border border-border bg-muted/50 px-3 py-2 text-sm text-muted-foreground"
        >
            This letter is queued for sending. Reload in a moment to see the
            result.
        </p>

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
                        Sent with the letter and CV attached.
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
                    The cover email is sent with the letter and your CV
                    attached. The company moves to Sent.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-1 rounded-md border border-border px-3 py-2">
                <span class="text-xs text-muted-foreground">Recipient</span>
                <span class="font-mono text-sm break-all">
                    {{ recipient ?? 'No email address on this company' }}
                </span>
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
                    Send
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
