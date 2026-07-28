<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, FileText, Pencil, Plus, Trash2 } from '@lucide/vue';
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
import { destroy, edit, index } from '@/routes/companies';
import { store as generateLetterRoute } from '@/routes/letters';
import { edit as editLetter } from '@/routes/letters';
import type { Company, LetterSummary } from '@/types';

const props = defineProps<{
    company: Company;
    letters: LetterSummary[];
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
    { label: 'Email', value: props.company.email },
    { label: 'City', value: props.company.city },
    { label: 'KvK number', value: props.company.kvk_number },
    { label: 'Industry', value: props.company.industry },
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
                </div>
                <a
                    v-if="company.website"
                    :href="websiteUrl(company.website)"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-sm text-primary underline-offset-4 hover:underline"
                >
                    {{ company.website }}
                </a>
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
