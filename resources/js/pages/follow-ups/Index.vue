<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Ban,
    BellOff,
    CalendarClock,
    ChevronLeft,
    ChevronRight,
    Clock3,
} from '@lucide/vue';
import { computed } from 'vue';
import CompanyStatusBadge from '@/components/companies/CompanyStatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { followUp as scheduleFollowUp, show } from '@/routes/companies';
import { index as followUpsIndex, snooze } from '@/routes/follow-ups';
import type { CompanyStatus, Paginated } from '@/types';

type FollowUp = {
    id: number;
    name: string;
    email: string | null;
    contact_name: string | null;
    status: CompanyStatus;
    do_not_contact: boolean;
    follow_up_at: string | null;
    last_contact_at: string | null;
    group: 'overdue' | 'today' | 'upcoming';
};

const props = defineProps<{
    followUps: Paginated<FollowUp>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Follow-ups',
                href: followUpsIndex(),
            },
        ],
    },
});

const sections = [
    { key: 'overdue', title: 'Overdue', empty: 'Nothing overdue.' },
    { key: 'today', title: 'Due today', empty: 'Nothing due today.' },
    { key: 'upcoming', title: 'Upcoming', empty: 'Nothing scheduled.' },
] as const;

const grouped = computed(() =>
    sections.map((section) => ({
        ...section,
        rows: props.followUps.data.filter((row) => row.group === section.key),
    })),
);

const formatDate = (value: string | null) =>
    value ? new Date(value).toLocaleDateString() : '-';

const goToPage = (page: number) => {
    router.get(
        followUpsIndex().url,
        { page },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const snoozeFollowUp = (id: number) => {
    router.patch(snooze(id).url, {}, { preserveScroll: true });
};

const clearFollowUp = (id: number) => {
    router.patch(
        scheduleFollowUp(id).url,
        { follow_up_at: null },
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Follow-ups" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            title="Follow-ups"
            description="Companies with a reminder set, oldest first. Closed companies are left out."
        />

        <div
            v-if="followUps.total === 0"
            class="flex flex-1 flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border"
        >
            <CalendarClock class="size-8 text-muted-foreground" />
            <p class="text-sm font-medium">No follow-ups scheduled</p>
            <p class="text-sm text-muted-foreground">
                Set a reminder on a company to see it here.
            </p>
        </div>

        <template v-else>
            <section
                v-for="section in grouped"
                :key="section.key"
                class="flex flex-col gap-3"
            >
                <div class="flex items-center gap-3">
                    <h2 class="text-sm font-medium">{{ section.title }}</h2>
                    <span
                        class="text-xs text-muted-foreground tabular-nums"
                        :class="{
                            'text-destructive':
                                section.key === 'overdue' &&
                                section.rows.length > 0,
                        }"
                    >
                        {{ section.rows.length }}
                    </span>
                </div>

                <p
                    v-if="section.rows.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{ section.empty }}
                </p>

                <Card v-for="row in section.rows" :key="row.id">
                    <CardContent
                        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex min-w-0 flex-col gap-1">
                            <div class="flex items-center gap-2">
                                <Link
                                    :href="show(row.id)"
                                    class="truncate font-medium hover:underline"
                                >
                                    {{ row.name }}
                                </Link>
                                <CompanyStatusBadge :status="row.status" />
                                <Ban
                                    v-if="row.do_not_contact"
                                    class="size-3.5 shrink-0 text-destructive"
                                    aria-label="Do not contact"
                                />
                            </div>
                            <p class="text-sm text-muted-foreground">
                                Due {{ formatDate(row.follow_up_at) }} &middot;
                                last letter sent
                                {{ formatDate(row.last_contact_at) }}
                                <template v-if="row.contact_name">
                                    &middot; {{ row.contact_name }}
                                </template>
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="snoozeFollowUp(row.id)"
                            >
                                <Clock3 />
                                Snooze a week
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="clearFollowUp(row.id)"
                            >
                                <BellOff />
                                Clear
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </section>

            <div
                v-if="followUps.last_page > 1"
                class="flex items-center justify-between gap-3"
            >
                <p class="text-sm text-muted-foreground">
                    Showing {{ followUps.from }} to {{ followUps.to }} of
                    {{ followUps.total }}
                </p>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="followUps.current_page === 1"
                        @click="goToPage(followUps.current_page - 1)"
                    >
                        <ChevronLeft />
                        Previous
                    </Button>
                    <span class="text-sm text-muted-foreground tabular-nums">
                        Page {{ followUps.current_page }} of
                        {{ followUps.last_page }}
                    </span>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="
                            followUps.current_page === followUps.last_page
                        "
                        @click="goToPage(followUps.current_page + 1)"
                    >
                        Next
                        <ChevronRight />
                    </Button>
                </div>
            </div>
        </template>
    </div>
</template>
