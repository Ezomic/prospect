<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Ban,
    FileText,
    Merge,
    Pencil,
    Plus,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import CompanyStatusBadge from '@/components/companies/CompanyStatusBadge.vue';
import InputError from '@/components/InputError.vue';
import LetterStatusBadge from '@/components/letters/LetterStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
    destroy,
    doNotContact as updateDoNotContact,
    edit,
    followUp as scheduleFollowUp,
    index,
    merge,
    status as updateStatus,
} from '@/routes/companies';
import { store as storeInteraction } from '@/routes/interactions';
import { destroy as destroyInteraction } from '@/routes/interactions';
import { store as generateLetterRoute } from '@/routes/letters';
import { edit as editLetter } from '@/routes/letters';
import type {
    Company,
    CompanyStatus,
    LetterSummary,
    TimelineEntry,
} from '@/types';

const props = defineProps<{
    company: Company;
    letters: LetterSummary[];
    timeline: TimelineEntry[];
    interactionKinds: { value: string; label: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Companies',
                href: index(),
            },
            {
                title: 'Company',
                href: index(),
            },
        ],
    },
});

const websiteUrl = (website: string) =>
    /^https?:\/\//.test(website) ? website : `https://${website}`;

const details = [
    { label: 'Contact', value: props.company.contact_name },
    { label: 'Contact role', value: props.company.contact_role },
    { label: 'Email', value: props.company.email },
    { label: 'City', value: props.company.city },
    { label: 'KvK number', value: props.company.kvk_number },
    { label: 'Industry', value: props.company.industry },
    {
        label: 'Lead score',
        value:
            props.company.lead_score !== null
                ? String(props.company.lead_score)
                : null,
    },
    { label: 'Source', value: props.company.source },
    { label: 'First contact', value: props.company.first_contact_channel },
];

const deleteOpen = ref(false);
const deleting = ref(false);

const confirmDelete = () => {
    router.delete(destroy(props.company.id).url, {
        onStart: () => (deleting.value = true),
        onFinish: () => (deleting.value = false),
    });
};

const generating = ref(false);

const generateLetter = (type: 'open_aanbod' | 'follow_up') => {
    router.post(
        generateLetterRoute(props.company.id).url,
        { type },
        {
            onStart: () => (generating.value = true),
            onFinish: () => (generating.value = false),
        },
    );
};

// A follow-up refers back to a letter that went out, so it is only offered
// once one has.
const hasSentLetter = computed(() =>
    props.letters.some((letter) => letter.sent_at !== null),
);

const formatDate = (value: string | null) =>
    value ? new Date(value).toLocaleDateString() : '-';

const setStatus = (status: CompanyStatus) => {
    router.patch(
        updateStatus(props.company.id).url,
        { status },
        { preserveScroll: true },
    );
};

const followUpDate = ref(props.company.follow_up_at?.slice(0, 10) ?? '');

const saveFollowUp = () => {
    router.patch(
        scheduleFollowUp(props.company.id).url,
        { follow_up_at: followUpDate.value || null },
        { preserveScroll: true },
    );
};

const clearFollowUp = () => {
    followUpDate.value = '';
    saveFollowUp();
};

const optOutOpen = ref(false);
const optOutReason = ref('');
const savingOptOut = ref(false);

const setDoNotContact = (flagged: boolean, reason = '') => {
    router.patch(
        updateDoNotContact(props.company.id).url,
        { do_not_contact: flagged, reason: reason || null },
        {
            preserveScroll: true,
            onStart: () => (savingOptOut.value = true),
            onFinish: () => (savingOptOut.value = false),
            onSuccess: () => {
                optOutOpen.value = false;
                optOutReason.value = '';
            },
        },
    );
};

const formatDateTime = (value: string | null) =>
    value ? new Date(value).toLocaleString() : '-';

const timelineDotClass = (kind: string) =>
    ({
        letter_sent: 'bg-primary',
        letter_generated: 'bg-muted-foreground',
        reply: 'bg-green-500',
        bounce: 'bg-destructive',
        do_not_contact: 'bg-destructive',
        interaction: 'bg-amber-500',
        added: 'bg-muted-foreground/50',
    })[kind] ?? 'bg-muted-foreground';

const interactionOpen = ref(false);

const interactionForm = useForm({
    kind: 'call',
    occurred_at: new Date().toISOString().slice(0, 16),
    summary: '',
});

const submitInteraction = () => {
    interactionForm.post(storeInteraction(props.company.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            interactionForm.reset('summary');
            interactionOpen.value = false;
        },
    });
};

const removeInteraction = (id: number) => {
    router.delete(destroyInteraction(id).url, { preserveScroll: true });
};
</script>

<template>
    <Head :title="company.name" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ company.name }}
                    </h1>
                    <CompanyStatusBadge :status="company.status" />
                    <span
                        v-if="company.do_not_contact"
                        class="inline-flex items-center gap-1 rounded-md border border-destructive/50 px-2 py-0.5 text-xs font-medium text-destructive"
                    >
                        <Ban class="size-3" />
                        Do not contact
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <a
                        v-if="company.website"
                        :href="websiteUrl(company.website)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-primary underline-offset-4 hover:underline"
                    >
                        {{ company.website }}
                    </a>
                    <a
                        v-if="company.linkedin_url"
                        :href="company.linkedin_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-primary underline-offset-4 hover:underline"
                    >
                        LinkedIn
                    </a>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Button variant="outline" as-child>
                    <Link :href="index()">
                        <ArrowLeft />
                        Back
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="merge(company.id)">
                        <Merge />
                        Merge
                    </Link>
                </Button>
                <Button as-child>
                    <Link :href="edit(company.id)">
                        <Pencil />
                        Edit
                    </Link>
                </Button>
                <Button
                    variant="outline"
                    class="text-destructive hover:text-destructive"
                    @click="deleteOpen = true"
                >
                    <Trash2 />
                    Delete
                </Button>
            </div>
        </div>

        <Card>
            <CardContent>
                <dl class="grid gap-6 sm:grid-cols-2">
                    <div
                        v-for="detail in details"
                        :key="detail.label"
                        class="grid gap-1"
                    >
                        <dt class="text-sm text-muted-foreground">
                            {{ detail.label }}
                        </dt>
                        <dd class="text-sm font-medium">
                            {{ detail.value ?? '-' }}
                        </dd>
                    </div>
                </dl>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-sm text-muted-foreground">Pipeline</h2>
                    <CompanyStatusBadge :status="company.status" />
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        @click="setStatus('replied')"
                    >
                        Mark replied
                    </Button>
                    <Button
                        size="sm"
                        variant="outline"
                        @click="setStatus('bounced')"
                    >
                        Mark bounced
                    </Button>
                    <Button
                        size="sm"
                        variant="outline"
                        @click="setStatus('closed')"
                    >
                        Mark closed
                    </Button>
                </div>

                <dl
                    v-if="company.replied_at || company.bounced_at"
                    class="grid gap-2 text-sm sm:grid-cols-2"
                >
                    <div v-if="company.replied_at" class="grid gap-1">
                        <dt class="text-muted-foreground">Replied</dt>
                        <dd class="font-medium">
                            {{ formatDate(company.replied_at) }}
                        </dd>
                    </div>
                    <div v-if="company.bounced_at" class="grid gap-1">
                        <dt class="text-muted-foreground">Bounced</dt>
                        <dd class="font-medium">
                            {{ formatDate(company.bounced_at) }}
                        </dd>
                    </div>
                </dl>

                <div class="flex flex-col gap-2 border-t pt-4">
                    <div
                        v-if="company.do_not_contact"
                        class="flex flex-col gap-2 rounded-md border border-destructive/50 px-3 py-2"
                    >
                        <p class="text-sm font-medium text-destructive">
                            Marked do not contact on
                            {{ formatDateTime(company.do_not_contact_at) }}
                        </p>
                        <p
                            v-if="company.do_not_contact_reason"
                            class="text-sm text-muted-foreground"
                        >
                            {{ company.do_not_contact_reason }}
                        </p>
                        <div>
                            <Button
                                size="sm"
                                variant="outline"
                                :disabled="savingOptOut"
                                @click="setDoNotContact(false)"
                            >
                                Allow contact again
                            </Button>
                        </div>
                    </div>
                    <div v-else>
                        <Button
                            size="sm"
                            variant="outline"
                            class="text-destructive hover:text-destructive"
                            @click="optOutOpen = true"
                        >
                            <Ban />
                            Mark do not contact
                        </Button>
                    </div>
                </div>

                <div
                    v-if="!company.do_not_contact"
                    class="flex flex-col gap-2 border-t pt-4"
                >
                    <Label for="follow_up">Follow-up reminder</Label>
                    <div class="flex flex-wrap items-center gap-2">
                        <input
                            id="follow_up"
                            v-model="followUpDate"
                            type="date"
                            class="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                        <Button size="sm" @click="saveFollowUp">Save</Button>
                        <Button
                            v-if="company.follow_up_at"
                            size="sm"
                            variant="ghost"
                            @click="clearFollowUp"
                        >
                            Clear
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-sm text-muted-foreground">Activity</h2>
                    <Button
                        size="sm"
                        variant="outline"
                        @click="interactionOpen = true"
                    >
                        <Plus />
                        Log interaction
                    </Button>
                </div>

                <p
                    v-if="timeline.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nothing has happened yet.
                </p>

                <ol v-else class="flex flex-col gap-4">
                    <li
                        v-for="(entry, index) in timeline"
                        :key="`${entry.at}-${index}`"
                        class="flex gap-3"
                    >
                        <div class="flex flex-col items-center">
                            <span
                                class="mt-1.5 size-2 shrink-0 rounded-full"
                                :class="timelineDotClass(entry.kind)"
                            />
                            <span
                                v-if="index < timeline.length - 1"
                                class="w-px flex-1 bg-border"
                            />
                        </div>

                        <div class="flex min-w-0 flex-1 flex-col gap-1 pb-1">
                            <div class="flex flex-wrap items-baseline gap-2">
                                <span class="text-sm font-medium">
                                    {{ entry.title }}
                                </span>
                                <span class="text-xs text-muted-foreground">
                                    {{ formatDateTime(entry.at) }}
                                </span>
                                <Link
                                    v-if="entry.letter_id"
                                    :href="editLetter(entry.letter_id)"
                                    class="text-xs text-primary underline-offset-4 hover:underline"
                                >
                                    Open letter
                                </Link>
                                <button
                                    v-if="entry.interaction_id"
                                    type="button"
                                    class="text-xs text-muted-foreground underline-offset-4 hover:text-destructive hover:underline"
                                    @click="
                                        removeInteraction(entry.interaction_id)
                                    "
                                >
                                    Remove
                                </button>
                            </div>
                            <p
                                v-if="entry.detail"
                                class="max-h-60 overflow-y-auto text-sm whitespace-pre-line text-muted-foreground"
                            >
                                {{ entry.detail }}
                            </p>
                        </div>
                    </li>
                </ol>
            </CardContent>
        </Card>

        <Card>
            <CardContent>
                <h2 class="mb-2 text-sm text-muted-foreground">Notes</h2>
                <p v-if="company.notes" class="text-sm whitespace-pre-line">
                    {{ company.notes }}
                </p>
                <p v-else class="text-sm text-muted-foreground">
                    No notes yet.
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-sm text-muted-foreground">Letters</h2>
                    <div class="flex items-center gap-2">
                        <Button
                            size="sm"
                            variant="outline"
                            :disabled="generating"
                            @click="generateLetter('open_aanbod')"
                        >
                            <Plus />
                            Open aanbod
                        </Button>
                        <Button
                            v-if="hasSentLetter"
                            size="sm"
                            variant="outline"
                            :disabled="generating"
                            @click="generateLetter('follow_up')"
                        >
                            <Plus />
                            Follow-up
                        </Button>
                    </div>
                </div>

                <p
                    v-if="letters.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No letters yet. Generate an open-aanbod letter to get
                    started.
                </p>

                <ul v-else class="flex flex-col divide-y divide-border">
                    <li
                        v-for="letter in letters"
                        :key="letter.id"
                        class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <FileText
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                            <Link
                                :href="editLetter(letter.id)"
                                class="truncate text-sm font-medium hover:underline"
                            >
                                {{ letter.subject }}
                            </Link>
                            <LetterStatusBadge :status="letter.status" />
                        </div>
                        <span class="shrink-0 text-sm text-muted-foreground">
                            {{ formatDate(letter.generated_at) }}
                        </span>
                    </li>
                </ul>
            </CardContent>
        </Card>
    </div>

    <Dialog v-model:open="interactionOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Log an interaction</DialogTitle>
                <DialogDescription>
                    Something that happened with {{ company.name }}, recorded on
                    the timeline.
                </DialogDescription>
            </DialogHeader>
            <form
                id="interaction-form"
                class="grid gap-4"
                @submit.prevent="submitInteraction"
            >
                <div class="grid gap-2">
                    <Label for="interaction_kind">Kind</Label>
                    <Select v-model="interactionForm.kind">
                        <SelectTrigger id="interaction_kind" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in interactionKinds"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="interactionForm.errors.kind" />
                </div>

                <div class="grid gap-2">
                    <Label for="interaction_at">When</Label>
                    <input
                        id="interaction_at"
                        v-model="interactionForm.occurred_at"
                        type="datetime-local"
                        required
                        class="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    />
                    <InputError :message="interactionForm.errors.occurred_at" />
                </div>

                <div class="grid gap-2">
                    <Label for="interaction_summary">What happened</Label>
                    <Textarea
                        id="interaction_summary"
                        v-model="interactionForm.summary"
                        required
                        placeholder="Gebeld met Jane, vraagt om een voorstel"
                    />
                    <InputError :message="interactionForm.errors.summary" />
                </div>
            </form>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancel</Button>
                </DialogClose>
                <Button
                    type="submit"
                    form="interaction-form"
                    :disabled="interactionForm.processing"
                >
                    Log it
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="optOutOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Mark do not contact?</DialogTitle>
                <DialogDescription>
                    {{ company.name }} will be refused for sending and dropped
                    from follow-ups, so no reminder can bring it back around.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-2">
                <Label for="opt_out_reason">Reason (optional)</Label>
                <Textarea
                    id="opt_out_reason"
                    v-model="optOutReason"
                    placeholder="Asked by email to be removed"
                />
            </div>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancel</Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    :disabled="savingOptOut"
                    @click="setDoNotContact(true, optOutReason)"
                >
                    <Ban />
                    Mark do not contact
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="deleteOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete company?</DialogTitle>
                <DialogDescription>
                    This permanently removes {{ company.name }} from your
                    outreach list. This cannot be undone.
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
