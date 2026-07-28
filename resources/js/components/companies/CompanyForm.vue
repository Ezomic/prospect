<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { index, store, update } from '@/routes/companies';
import type { Company, CompanyStatus, CompanyStatusOption } from '@/types';

const props = defineProps<{
    company?: Company;
    statuses: CompanyStatusOption[];
}>();

const form = useForm({
    name: props.company?.name ?? '',
    website: props.company?.website ?? '',
    email: props.company?.email ?? '',
    contact_name: props.company?.contact_name ?? '',
    city: props.company?.city ?? '',
    kvk_number: props.company?.kvk_number ?? '',
    industry: props.company?.industry ?? '',
    status: (props.company?.status ?? 'new') as CompanyStatus,
    notes: props.company?.notes ?? '',
});

const submit = () => {
    form.submit(props.company ? update(props.company.id) : store());
};
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-6 sm:grid-cols-2">
            <div class="grid gap-2 sm:col-span-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    required
                    placeholder="Company name"
                />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="contact_name">Contact</Label>
                <Input
                    id="contact_name"
                    v-model="form.contact_name"
                    placeholder="Contact person"
                />
                <InputError :message="form.errors.contact_name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    v-model="form.email"
                    placeholder="info@example.com"
                />
                <InputError :message="form.errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="website">Website</Label>
                <Input
                    id="website"
                    v-model="form.website"
                    placeholder="example.com"
                />
                <InputError :message="form.errors.website" />
            </div>

            <div class="grid gap-2">
                <Label for="city">City</Label>
                <Input id="city" v-model="form.city" placeholder="City" />
                <InputError :message="form.errors.city" />
            </div>

            <div class="grid gap-2">
                <Label for="kvk_number">KvK number</Label>
                <Input
                    id="kvk_number"
                    v-model="form.kvk_number"
                    placeholder="12345678"
                />
                <InputError :message="form.errors.kvk_number" />
            </div>

            <div class="grid gap-2">
                <Label for="industry">Industry</Label>
                <Input
                    id="industry"
                    v-model="form.industry"
                    placeholder="Software"
                />
                <InputError :message="form.errors.industry" />
            </div>

            <div class="grid gap-2">
                <Label for="status">Status</Label>
                <Select v-model="form.status">
                    <SelectTrigger id="status" class="w-full">
                        <SelectValue placeholder="Select a status" />
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
                <InputError :message="form.errors.status" />
            </div>

            <div class="grid gap-2 sm:col-span-2">
                <Label for="notes">Notes</Label>
                <Textarea
                    id="notes"
                    v-model="form.notes"
                    placeholder="Anything worth remembering about this company"
                />
                <InputError :message="form.errors.notes" />
            </div>
        </div>

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="form.processing">
                {{ company ? 'Save changes' : 'Create company' }}
            </Button>
            <Button variant="ghost" as-child>
                <Link :href="index()">Cancel</Link>
            </Button>
        </div>
    </form>
</template>
