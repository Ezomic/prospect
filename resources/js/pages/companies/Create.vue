<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CompanyForm from '@/components/companies/CompanyForm.vue';
import Heading from '@/components/Heading.vue';
import { create, index } from '@/routes/companies';
import type { CompanyStatusOption } from '@/types';

defineProps<{
    statuses: CompanyStatusOption[];
    languages: { value: string; label: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Companies',
                href: index(),
            },
            {
                title: 'New company',
                href: create(),
            },
        ],
    },
});
</script>

<template>
    <Head title="New company" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            title="New company"
            description="Add a company to your outreach list."
        />

        <div class="max-w-3xl">
            <CompanyForm :statuses="statuses" :languages="languages" />
        </div>
    </div>
</template>
