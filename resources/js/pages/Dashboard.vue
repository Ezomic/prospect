<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BellRing, Building2, ChevronRight, MailX } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Card } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index as companiesIndex } from '@/routes/companies';
import { index as followUpsIndex } from '@/routes/follow-ups';
import type { CompanyStatus } from '@/types';

defineProps<{
    total: number;
    stats: { value: CompanyStatus; label: string; count: number }[];
    followUpsDue: number;
    missingEmail: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const dotColors: Record<CompanyStatus, string> = {
    new: 'bg-muted-foreground',
    sent: 'bg-primary',
    replied: 'bg-green-500',
    bounced: 'bg-destructive',
    closed: 'bg-muted-foreground/50',
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            title="Dashboard"
            description="Your outreach pipeline at a glance."
        />

        <Link v-if="followUpsDue > 0" :href="followUpsIndex()" class="group">
            <Card
                class="flex items-center justify-between gap-4 border-amber-500/40 bg-amber-500/5 p-5 transition-colors group-hover:border-amber-500/70"
            >
                <div class="flex items-center gap-3">
                    <BellRing
                        class="size-5 text-amber-600 dark:text-amber-500"
                    />
                    <div>
                        <p class="text-sm font-medium">Follow-ups due</p>
                        <p class="text-sm text-muted-foreground">
                            {{ followUpsDue }}
                            {{
                                followUpsDue === 1
                                    ? 'company needs'
                                    : 'companies need'
                            }}
                            a follow-up.
                        </p>
                    </div>
                </div>
                <ChevronRight class="size-4 text-muted-foreground" />
            </Card>
        </Link>

        <Link
            v-if="missingEmail > 0"
            :href="companiesIndex({ query: { missing_email: 1 } })"
            class="group"
        >
            <Card
                class="flex items-center justify-between gap-4 p-5 transition-colors group-hover:border-primary/50"
            >
                <div class="flex items-center gap-3">
                    <MailX class="size-5 text-muted-foreground" />
                    <div>
                        <p class="text-sm font-medium">No email address</p>
                        <p class="text-sm text-muted-foreground">
                            {{ missingEmail }}
                            {{ missingEmail === 1 ? 'company' : 'companies' }}
                            cannot be contacted until an address is filled in.
                        </p>
                    </div>
                </div>
                <ChevronRight class="size-4 text-muted-foreground" />
            </Card>
        </Link>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link :href="companiesIndex()" class="group">
                <Card
                    class="flex h-full flex-col justify-between gap-4 p-5 transition-colors group-hover:border-primary/50"
                >
                    <div
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <Building2 class="size-4" />
                        All companies
                    </div>
                    <p class="text-3xl font-semibold tracking-tight">
                        {{ total }}
                    </p>
                </Card>
            </Link>

            <Link
                v-for="stat in stats"
                :key="stat.value"
                :href="companiesIndex({ query: { status: stat.value } })"
                class="group"
            >
                <Card
                    class="flex h-full flex-col justify-between gap-4 p-5 transition-colors group-hover:border-primary/50"
                >
                    <div
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <span
                            class="size-2 rounded-full"
                            :class="dotColors[stat.value]"
                        />
                        {{ stat.label }}
                    </div>
                    <p class="text-3xl font-semibold tracking-tight">
                        {{ stat.count }}
                    </p>
                </Card>
            </Link>
        </div>
    </div>
</template>
