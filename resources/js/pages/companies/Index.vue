<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Ban,
    Building2,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
    MailX,
    Pencil,
    Plus,
    Search,
    SearchX,
    Trash2,
    Upload,
    X,
} from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, ref } from 'vue';
import CompanyStatusBadge from '@/components/companies/CompanyStatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    create,
    destroy,
    edit,
    index as companiesIndex,
    show,
} from '@/routes/companies';
import { bulk } from '@/routes/companies';
import { create as importCompanies } from '@/routes/companies/import';
import type {
    Company,
    CompanySort,
    CompanyStatus,
    CompanyStatusOption,
    Paginated,
} from '@/types';

const props = defineProps<{
    companies: Paginated<Company>;
    filters: {
        search: string | null;
        status: CompanyStatus | null;
        sort: CompanySort;
        direction: 'asc' | 'desc';
        missing_email: boolean;
    };
    missingEmailCount: number;
    statuses: CompanyStatusOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Companies',
                href: companiesIndex(),
            },
        ],
    },
});

const search = ref(props.filters.search ?? '');
const status = ref<CompanyStatus | 'all'>(props.filters.status ?? 'all');
const missingEmail = ref(props.filters.missing_email);

const query = (
    overrides: Record<string, string | number | undefined> = {},
) => ({
    search: search.value || undefined,
    status: status.value === 'all' ? undefined : status.value,
    sort: props.filters.sort === 'name' ? undefined : props.filters.sort,
    direction: props.filters.direction === 'asc' ? undefined : 'desc',
    missing_email: missingEmail.value ? 1 : undefined,
    ...overrides,
});

const visit = (overrides: Record<string, string | number | undefined> = {}) => {
    router.get(companiesIndex().url, query(overrides), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Changing a filter invalidates the current page number.
const applyFilters = () => visit({ page: undefined });

watchDebounced([search, status, missingEmail], applyFilters, {
    debounce: 300,
});

const columns: { key: CompanySort; label: string }[] = [
    { key: 'name', label: 'Name' },
    { key: 'lead_score', label: 'Score' },
    { key: 'status', label: 'Status' },
    { key: 'last_contact', label: 'Last contact' },
];

const sortBy = (column: CompanySort) => {
    const direction =
        props.filters.sort === column && props.filters.direction === 'asc'
            ? 'desc'
            : 'asc';

    visit({ sort: column, direction, page: undefined });
};

const formatDate = (value: string | null | undefined) =>
    value ? new Date(value).toLocaleDateString() : '-';

const hasActiveFilters = computed(
    () => search.value !== '' || status.value !== 'all' || missingEmail.value,
);

const clearFilters = () => {
    search.value = '';
    status.value = 'all';
    missingEmail.value = false;
};

const selected = ref<number[]>([]);

// Only ever the rows on screen. A select-all-across-pages would act on
// companies the user cannot see, which is how bulk tools cause damage.
const allOnPageSelected = computed(
    () =>
        props.companies.data.length > 0 &&
        props.companies.data.every((company) =>
            selected.value.includes(company.id),
        ),
);

const toggleAllOnPage = (checked: boolean) => {
    const ids = props.companies.data.map((company) => company.id);

    selected.value = checked
        ? [...new Set([...selected.value, ...ids])]
        : selected.value.filter((id) => !ids.includes(id));
};

const toggleRow = (id: number, checked: boolean) => {
    selected.value = checked
        ? [...selected.value, id]
        : selected.value.filter((value) => value !== id);
};

const bulkAction = ref('');
const bulkStatus = ref<CompanyStatus>('new');
const bulkReason = ref('');
const applying = ref(false);

const applyBulk = () => {
    if (selected.value.length === 0 || bulkAction.value === '') {
        return;
    }

    router.post(
        bulk().url,
        {
            ids: selected.value,
            action: bulkAction.value,
            status: bulkStatus.value,
            reason: bulkReason.value || null,
        },
        {
            preserveScroll: true,
            onStart: () => (applying.value = true),
            onFinish: () => (applying.value = false),
            onSuccess: () => {
                selected.value = [];
                bulkAction.value = '';
                bulkReason.value = '';
            },
        },
    );
};

const websiteUrl = (website: string) =>
    /^https?:\/\//.test(website) ? website : `https://${website}`;

const deleteOpen = ref(false);
const deleting = ref(false);
const pendingDelete = ref<Company | null>(null);

const askDelete = (company: Company) => {
    pendingDelete.value = company;
    deleteOpen.value = true;
};

const confirmDelete = () => {
    if (!pendingDelete.value) {
        return;
    }

    router.delete(destroy(pendingDelete.value.id).url, {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onFinish: () => (deleting.value = false),
        onSuccess: () => (deleteOpen.value = false),
    });
};
</script>

<template>
    <Head title="Companies" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Companies"
                description="Companies to pitch and track through the outreach pipeline."
            />
            <div class="flex items-center gap-2">
                <Button variant="outline" as-child>
                    <Link :href="importCompanies()">
                        <Upload />
                        Import
                    </Link>
                </Button>
                <Button as-child>
                    <Link :href="create()">
                        <Plus />
                        Add company
                    </Link>
                </Button>
            </div>
        </div>

        <div
            v-if="companies.total > 0 || hasActiveFilters"
            class="flex flex-col gap-3 sm:flex-row sm:items-center"
        >
            <div class="relative w-full sm:max-w-xs">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Search companies"
                    class="pl-9"
                    aria-label="Search companies"
                />
            </div>

            <Select v-model="status">
                <SelectTrigger
                    class="w-full sm:w-48"
                    aria-label="Filter by status"
                >
                    <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All statuses</SelectItem>
                    <SelectItem
                        v-for="option in statuses"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Button
                v-if="missingEmailCount > 0 || missingEmail"
                :variant="missingEmail ? 'default' : 'outline'"
                :aria-pressed="missingEmail"
                @click="missingEmail = !missingEmail"
            >
                <MailX />
                No email ({{ missingEmailCount }})
            </Button>

            <Button
                v-if="hasActiveFilters"
                variant="ghost"
                class="sm:ml-auto"
                @click="clearFilters"
            >
                <X />
                Clear
            </Button>
        </div>

        <div
            v-if="selected.length > 0"
            class="flex flex-col gap-3 rounded-xl border border-primary/40 bg-primary/5 p-3 sm:flex-row sm:items-center"
        >
            <span class="text-sm font-medium">
                {{ selected.length }} selected
            </span>

            <Select v-model="bulkAction">
                <SelectTrigger class="w-full sm:w-56" aria-label="Bulk action">
                    <SelectValue placeholder="Choose an action" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="status">Set status</SelectItem>
                    <SelectItem value="do_not_contact">
                        Mark do not contact
                    </SelectItem>
                    <SelectItem value="clear_follow_up">
                        Clear follow-up
                    </SelectItem>
                    <SelectItem value="generate_letter">
                        Generate draft letters
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-if="bulkAction === 'status'" v-model="bulkStatus">
                <SelectTrigger class="w-full sm:w-40" aria-label="New status">
                    <SelectValue />
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

            <Input
                v-if="bulkAction === 'do_not_contact'"
                v-model="bulkReason"
                placeholder="Reason (optional)"
                class="w-full sm:w-64"
            />

            <div class="flex items-center gap-2 sm:ml-auto">
                <Button
                    size="sm"
                    :disabled="applying || bulkAction === ''"
                    @click="applyBulk"
                >
                    Apply
                </Button>
                <Button size="sm" variant="ghost" @click="selected = []">
                    Clear selection
                </Button>
            </div>
        </div>

        <p
            v-if="selected.length > 0 && bulkAction === 'generate_letter'"
            class="text-xs text-muted-foreground"
        >
            Drafts only. Sending stays one letter at a time, with its preview
            and confirmation.
        </p>

        <div
            v-if="companies.data.length === 0 && !hasActiveFilters"
            class="flex flex-1 flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border"
        >
            <Building2 class="size-8 text-muted-foreground" />
            <p class="text-sm font-medium">No companies yet</p>
            <p class="text-sm text-muted-foreground">
                Add your first company to start tracking outreach.
            </p>
            <Button as-child class="mt-2">
                <Link :href="create()">
                    <Plus />
                    Add company
                </Link>
            </Button>
        </div>

        <div
            v-else-if="companies.data.length === 0"
            class="flex flex-1 flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border"
        >
            <SearchX class="size-8 text-muted-foreground" />
            <p class="text-sm font-medium">No companies match your filters</p>
            <p class="text-sm text-muted-foreground">
                Try a different search term or status.
            </p>
            <Button variant="outline" class="mt-2" @click="clearFilters">
                <X />
                Clear filters
            </Button>
        </div>

        <div
            v-else
            class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-sidebar-border/70 text-left text-muted-foreground dark:border-sidebar-border"
                    >
                        <th class="px-4 py-3 font-medium">
                            <Checkbox
                                :model-value="allOnPageSelected"
                                aria-label="Select every company on this page"
                                @update:model-value="
                                    (checked) =>
                                        toggleAllOnPage(checked === true)
                                "
                            />
                        </th>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            class="px-4 py-3 font-medium"
                        >
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 hover:text-foreground"
                                :aria-sort="
                                    filters.sort === column.key
                                        ? filters.direction === 'asc'
                                            ? 'ascending'
                                            : 'descending'
                                        : 'none'
                                "
                                @click="sortBy(column.key)"
                            >
                                {{ column.label }}
                                <ChevronUp
                                    v-if="
                                        filters.sort === column.key &&
                                        filters.direction === 'asc'
                                    "
                                    class="size-3"
                                />
                                <ChevronDown
                                    v-else-if="filters.sort === column.key"
                                    class="size-3"
                                />
                            </button>
                        </th>
                        <th class="px-4 py-3 font-medium">Contact</th>
                        <th class="px-4 py-3 font-medium">City</th>
                        <th class="px-4 py-3 font-medium">Industry</th>
                        <th class="px-4 py-3 font-medium">KvK</th>
                        <th class="px-4 py-3 font-medium">Website</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="company in companies.data"
                        :key="company.id"
                        class="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
                        :class="{
                            'bg-primary/5': selected.includes(company.id),
                        }"
                    >
                        <td class="px-4 py-3">
                            <Checkbox
                                :model-value="selected.includes(company.id)"
                                :aria-label="`Select ${company.name}`"
                                @update:model-value="
                                    (checked) =>
                                        toggleRow(company.id, checked === true)
                                "
                            />
                        </td>
                        <td class="px-4 py-3 font-medium">
                            <div class="flex items-center gap-2">
                                <Link
                                    :href="show(company.id)"
                                    class="hover:underline"
                                >
                                    {{ company.name }}
                                </Link>
                                <Ban
                                    v-if="company.do_not_contact"
                                    class="size-3.5 shrink-0 text-destructive"
                                    aria-label="Do not contact"
                                />
                            </div>
                        </td>
                        <td
                            class="px-4 py-3 text-muted-foreground tabular-nums"
                        >
                            {{ company.lead_score ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <CompanyStatusBadge :status="company.status" />
                        </td>
                        <td
                            class="px-4 py-3 text-muted-foreground tabular-nums"
                        >
                            {{ formatDate(company.last_contact_at) }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ company.contact_name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ company.city ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ company.industry ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ company.kvk_number ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <a
                                v-if="company.website"
                                :href="websiteUrl(company.website)"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-primary underline-offset-4 hover:underline"
                            >
                                {{ company.website }}
                            </a>
                            <span v-else class="text-muted-foreground">-</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <Button variant="ghost" size="icon" as-child>
                                    <Link
                                        :href="edit(company.id)"
                                        :aria-label="`Edit ${company.name}`"
                                    >
                                        <Pencil />
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="text-destructive hover:text-destructive"
                                    :aria-label="`Delete ${company.name}`"
                                    @click="askDelete(company)"
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="companies.data.length > 0"
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <p class="text-sm text-muted-foreground">
                Showing {{ companies.from }} to {{ companies.to }} of
                {{ companies.total }}
                {{ companies.total === 1 ? 'company' : 'companies' }}
            </p>

            <div v-if="companies.last_page > 1" class="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="companies.current_page === 1"
                    @click="visit({ page: companies.current_page - 1 })"
                >
                    <ChevronLeft />
                    Previous
                </Button>
                <span class="text-sm text-muted-foreground tabular-nums">
                    Page {{ companies.current_page }} of
                    {{ companies.last_page }}
                </span>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="companies.current_page === companies.last_page"
                    @click="visit({ page: companies.current_page + 1 })"
                >
                    Next
                    <ChevronRight />
                </Button>
            </div>
        </div>
    </div>

    <Dialog v-model:open="deleteOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete company?</DialogTitle>
                <DialogDescription>
                    This permanently removes {{ pendingDelete?.name }} from your
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
