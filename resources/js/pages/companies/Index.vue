<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Building2,
    Pencil,
    Plus,
    Search,
    SearchX,
    Trash2,
    X,
} from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, ref } from 'vue';
import CompanyStatusBadge from '@/components/companies/CompanyStatusBadge.vue';
import Heading from '@/components/Heading.vue';
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
import type { Company, CompanyStatus, CompanyStatusOption } from '@/types';

const props = defineProps<{
    companies: Company[];
    filters: { search: string | null; status: CompanyStatus | null };
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

const applyFilters = () => {
    router.get(
        companiesIndex().url,
        {
            search: search.value || undefined,
            status: status.value === 'all' ? undefined : status.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watchDebounced([search, status], applyFilters, { debounce: 300 });

const hasActiveFilters = computed(
    () => search.value !== '' || status.value !== 'all',
);

const clearFilters = () => {
    search.value = '';
    status.value = 'all';
};

const websiteUrl = (website: string) =>
    /^https?:\/\//.test(website) ? website : `https://${website}`;

const deleteOpen = ref(false);
const deleting = ref(false);
const selected = ref<Company | null>(null);

const askDelete = (company: Company) => {
    selected.value = company;
    deleteOpen.value = true;
};

const confirmDelete = () => {
    if (!selected.value) {
        return;
    }

    router.delete(destroy(selected.value.id).url, {
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
            <Button as-child>
                <Link :href="create()">
                    <Plus />
                    Add company
                </Link>
            </Button>
        </div>

        <div
            v-if="companies.length > 0 || hasActiveFilters"
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
            v-if="companies.length === 0 && !hasActiveFilters"
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
            v-else-if="companies.length === 0"
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
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Contact</th>
                        <th class="px-4 py-3 font-medium">City</th>
                        <th class="px-4 py-3 font-medium">Industry</th>
                        <th class="px-4 py-3 font-medium">KvK</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Website</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="company in companies"
                        :key="company.id"
                        class="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
                    >
                        <td class="px-4 py-3 font-medium">
                            <Link
                                :href="show(company.id)"
                                class="hover:underline"
                            >
                                {{ company.name }}
                            </Link>
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
                            <CompanyStatusBadge :status="company.status" />
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
    </div>

    <Dialog v-model:open="deleteOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete company?</DialogTitle>
                <DialogDescription>
                    This permanently removes {{ selected?.name }} from your
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
