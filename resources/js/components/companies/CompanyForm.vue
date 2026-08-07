<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
    source: props.company?.source ?? '',
    contact_role: props.company?.contact_role ?? '',
    linkedin_url: props.company?.linkedin_url ?? '',
    // Kept as the string the input actually holds, converted on submit. The
    // shadcn Input models string | number and is not ours to widen.
    lead_score: props.company?.lead_score?.toString() ?? '',
    first_contact_channel: props.company?.first_contact_channel ?? '',
    do_not_contact: props.company?.do_not_contact ?? false,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        lead_score: data.lead_score === '' ? null : Number(data.lead_score),
    })).submit(props.company ? update(props.company.id) : store());
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

            <div class="border-t pt-4 sm:col-span-2">
                <h2 class="text-sm font-medium">Lead qualification</h2>
                <p class="text-sm text-muted-foreground">
                    Where the lead came from and how to prioritise it.
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="source">Source</Label>
                <Input
                    id="source"
                    v-model="form.source"
                    placeholder="vacancy / directory / referral / ecosystem"
                />
                <InputError :message="form.errors.source" />
            </div>

            <div class="grid gap-2">
                <Label for="lead_score">Lead score (0-10)</Label>
                <Input
                    id="lead_score"
                    type="number"
                    min="0"
                    max="10"
                    v-model="form.lead_score"
                    placeholder="0-10"
                />
                <InputError :message="form.errors.lead_score" />
            </div>

            <div class="grid gap-2">
                <Label for="contact_role">Contact role</Label>
                <Input
                    id="contact_role"
                    v-model="form.contact_role"
                    placeholder="Founder / Lead developer / CTO"
                />
                <InputError :message="form.errors.contact_role" />
            </div>

            <div class="grid gap-2">
                <Label for="first_contact_channel">First contact channel</Label>
                <Input
                    id="first_contact_channel"
                    v-model="form.first_contact_channel"
                    placeholder="email / linkedin / phone"
                />
                <InputError :message="form.errors.first_contact_channel" />
            </div>

            <div class="grid gap-2 sm:col-span-2">
                <Label for="linkedin_url">LinkedIn URL</Label>
                <Input
                    id="linkedin_url"
                    v-model="form.linkedin_url"
                    placeholder="https://www.linkedin.com/in/..."
                />
                <InputError :message="form.errors.linkedin_url" />
            </div>

            <div class="flex items-start gap-3 sm:col-span-2">
                <Checkbox
                    id="do_not_contact"
                    v-model="form.do_not_contact"
                    class="mt-0.5"
                />
                <div class="grid gap-1">
                    <Label for="do_not_contact">Do not contact</Label>
                    <p class="text-sm text-muted-foreground">
                        Letters can still be drafted, but sending to this
                        company is refused.
                    </p>
                    <InputError :message="form.errors.do_not_contact" />
                </div>
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
