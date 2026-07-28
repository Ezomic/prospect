<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CompanyForm from '@/components/companies/CompanyForm.vue';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/companies';
import type { Company, CompanyStatusOption } from '@/types';

defineProps<{
    company: Company;
    statuses: CompanyStatusOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Companies',
                href: index(),
            },
            {
                title: 'Edit company',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Edit company" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading title="Edit company" :description="company.name" />

        <div class="max-w-3xl">
            <CompanyForm :company="company" :statuses="statuses" />
        </div>
    </div>
</template>
