<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Ban, FileText, Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import CompanyStatusBadge from '@/components/companies/CompanyStatusBadge.vue';
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
import { Textarea } from '@/components/ui/textarea';
import {
    destroy,
    doNotContact as updateDoNotContact,
    edit,
    followUp as scheduleFollowUp,
    index,
    status as updateStatus,
} from '@/routes/companies';
import { store as generateLetterRoute } from '@/routes/letters';
import { edit as editLetter } from '@/routes/letters';
import type {
    Company,
    CompanyStatus,
    InboundMessage,
    LetterSummary,
} from '@/types';

const props = defineProps<{
    company: Company;
    letters: LetterSummary[];
    messages: InboundMessage[];
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

const generateLetter = () => {
    router.post(
        generateLetterRoute(props.company.id).url,
        {},
        {
            onStart: () => (generating.value = true),
            onFinish: () => (generating.value = false),
        },
    );
};

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

        <Card v-if="messages.length > 0">
            <CardContent class="flex flex-col gap-4">
                <h2 class="text-sm text-muted-foreground">
                    Replies and bounces
                </h2>

                <article
                    v-for="message in messages"
                    :key="message.id"
                    class="flex flex-col gap-2 rounded-md border border-border p-3"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="rounded-md px-2 py-0.5 text-xs font-medium"
                            :class="
                                message.kind === 'bounce'
                                    ? 'border border-destructive/50 text-destructive'
                                    : 'border border-border text-muted-foreground'
                            "
                        >
                            {{ message.kind === 'bounce' ? 'Bounce' : 'Reply' }}
                        </span>
                        <span class="font-mono text-xs break-all">
                            {{ message.from }}
                        </span>
                        <span class="ml-auto text-xs text-muted-foreground">
                            {{ formatDateTime(message.received_at) }}
                        </span>
                    </div>

                    <p v-if="message.subject" class="text-sm font-medium">
                        {{ message.subject }}
                    </p>

                    <p
                        v-if="message.body"
                        class="max-h-72 overflow-y-auto text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ message.body }}
                    </p>
                    <p v-else class="text-sm text-muted-foreground">
                        No readable text in this message.
                    </p>
                </article>
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
                    <Button
                        size="sm"
                        variant="outline"
                        :disabled="generating"
                        @click="generateLetter"
                    >
                        <Plus />
                        Generate letter
                    </Button>
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
