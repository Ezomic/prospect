<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Merge } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index as companiesIndex, show } from '@/routes/companies';
import { apply as applyMerge } from '@/routes/companies/merge';
import type { Company } from '@/types';

const props = defineProps<{
    company: Company;
    candidates: Company[];
    fields: string[];
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

const duplicateId = ref<number | null>(props.candidates[0]?.id ?? null);

const duplicate = computed(
    () => props.candidates.find((c) => c.id === duplicateId.value) ?? null,
);

const value = (company: Company | null, field: string) => {
    if (!company) {
        return null;
    }

    return (company as unknown as Record<string, unknown>)[field] ?? null;
};

// Only fields where the two actually disagree need a decision. Anything else
// is either identical or filled in automatically from whichever side has it.
const conflicts = computed(() =>
    props.fields.filter((field) => {
        const ours = value(props.company, field);
        const theirs = value(duplicate.value, field);

        return ours !== null && theirs !== null && ours !== theirs;
    }),
);

const form = useForm<{
    duplicate_id: number | null;
    take_from_duplicate: string[];
}>({
    duplicate_id: null,
    take_from_duplicate: [],
});

const preferDuplicate = (field: string, prefer: boolean) => {
    form.take_from_duplicate = prefer
        ? [...form.take_from_duplicate, field]
        : form.take_from_duplicate.filter((f) => f !== field);
};

const submit = () => {
    form.duplicate_id = duplicateId.value;
    form.post(applyMerge(props.company.id).url);
};
</script>

<template>
    <Head :title="`Merge ${company.name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                :title="`Merge into ${company.name}`"
                description="The company you are on is kept. The one you choose is folded into it and removed."
            />
            <Button variant="outline" as-child>
                <Link :href="show(company.id)">
                    <ArrowLeft />
                    Back
                </Link>
            </Button>
        </div>

        <p
            v-if="candidates.length === 0"
            class="rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center text-sm text-muted-foreground dark:border-sidebar-border"
        >
            Nothing here looks like a duplicate of {{ company.name }}. Companies
            are matched on email address, KvK number or an identical name.
        </p>

        <template v-else>
            <Card>
                <CardContent class="flex flex-col gap-3">
                    <span class="text-sm text-muted-foreground">
                        Company to merge in
                    </span>
                    <Select v-model="duplicateId">
                        <SelectTrigger class="w-full sm:w-96">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="candidate in candidates"
                                :key="candidate.id"
                                :value="candidate.id"
                            >
                                {{ candidate.name }}
                                <template v-if="candidate.email">
                                    ({{ candidate.email }})
                                </template>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1">
                        <h2 class="text-sm font-medium">Conflicting fields</h2>
                        <p class="text-sm text-muted-foreground">
                            Only fields where the two disagree need a choice.
                            Anything blank on {{ company.name }} is filled in
                            from the other automatically, and pipeline state is
                            never chosen by hand.
                        </p>
                    </div>

                    <p
                        v-if="conflicts.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Nothing conflicts.
                    </p>

                    <div
                        v-for="field in conflicts"
                        :key="field"
                        class="grid gap-2 border-t pt-3 sm:grid-cols-[10rem_1fr_1fr] sm:items-center"
                    >
                        <span class="font-mono text-xs">{{ field }}</span>

                        <label class="flex items-center gap-2 text-sm">
                            <input
                                type="radio"
                                :name="field"
                                :checked="
                                    !form.take_from_duplicate.includes(field)
                                "
                                @change="preferDuplicate(field, false)"
                            />
                            <span class="truncate">
                                {{ value(company, field) }}
                            </span>
                        </label>

                        <label class="flex items-center gap-2 text-sm">
                            <input
                                type="radio"
                                :name="field"
                                :checked="
                                    form.take_from_duplicate.includes(field)
                                "
                                @change="preferDuplicate(field, true)"
                            />
                            <span class="truncate">
                                {{ value(duplicate, field) }}
                            </span>
                        </label>
                    </div>
                </CardContent>
            </Card>

            <div class="flex items-center gap-3">
                <Button
                    :disabled="form.processing || duplicateId === null"
                    @click="submit"
                >
                    <Merge />
                    Merge and remove {{ duplicate?.name }}
                </Button>
                <Button variant="ghost" as-child>
                    <Link :href="show(company.id)">Cancel</Link>
                </Button>
            </div>
        </template>
    </div>
</template>
