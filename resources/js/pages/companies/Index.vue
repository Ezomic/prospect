<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Building2 } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import type { BadgeVariants } from '@/components/ui/badge';
import { index as companiesIndex } from '@/routes/companies';
import type { Company, CompanyStatus } from '@/types';

defineProps<{
    companies: Company[];
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

const statusVariants: Record<CompanyStatus, BadgeVariants['variant']> = {
    new: 'secondary',
    sent: 'default',
    replied: 'default',
    bounced: 'destructive',
    closed: 'outline',
};

const statusLabels: Record<CompanyStatus, string> = {
    new: 'New',
    sent: 'Sent',
    replied: 'Replied',
    bounced: 'Bounced',
    closed: 'Closed',
};

const websiteUrl = (website: string) =>
    /^https?:\/\//.test(website) ? website : `https://${website}`;
</script>

<template>
    <Head title="Companies" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            title="Companies"
            description="Companies to pitch and track through the outreach pipeline."
        />

        <div
            v-if="companies.length === 0"
            class="flex flex-1 flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border"
        >
            <Building2 class="size-8 text-muted-foreground" />
            <p class="text-sm font-medium">No companies yet</p>
            <p class="text-sm text-muted-foreground">
                Companies you add to your outreach list will appear here.
            </p>
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
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="company in companies"
                        :key="company.id"
                        class="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ company.name }}
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
                            <Badge :variant="statusVariants[company.status]">{{
                                statusLabels[company.status]
                            }}</Badge>
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
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
