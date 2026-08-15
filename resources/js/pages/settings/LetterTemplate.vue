<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { RotateCcw } from '@lucide/vue';
import { computed, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { edit, update } from '@/routes/letter-template';

type Template = {
    subject: string;
    body: string;
    email_subject: string;
    email_body: string;
};

const props = defineProps<{
    type: string;
    types: { value: string; label: string; description: string }[];
    language: string;
    languages: { value: string; label: string }[];
    template: Template;
    defaults: Template;
    sample: Record<string, string>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Letter template',
                href: edit(),
            },
        ],
    },
});

const form = useForm({
    ...props.template,
    type: props.type,
    language: props.language,
});

// The form is seeded from the template that was loaded, so switching type is a
// fresh page visit rather than swapping the fields underneath unsaved edits.
watch(
    () => props.template,
    (template) =>
        form
            .defaults({
                ...template,
                type: props.type,
                language: props.language,
            })
            .reset(),
);

const submit = () => {
    form.patch(update().url, { preserveScroll: true });
};

const activeType = computed(
    () =>
        props.types.find((type) => type.value === props.type) ?? props.types[0],
);

const restoreDefaults = () => {
    form.subject = props.defaults.subject;
    form.body = props.defaults.body;
    form.email_subject = props.defaults.email_subject;
    form.email_body = props.defaults.email_body;
};

// Mirrors GenerateLetter::render: an unknown placeholder is left as written so
// a typo shows up in the preview rather than silently blanking a sentence.
const render = (template: string) =>
    template.replace(
        /\{\{\s*(\w+)\s*\}\}/g,
        (match, key: string) => props.sample[key] ?? match,
    );

const placeholders = [
    { key: 'company', what: 'Company name' },
    { key: 'contact', what: 'Contact person, blank when unknown' },
    { key: 'city', what: 'City, blank when unknown' },
    { key: 'industry', what: 'Industry, blank when unknown' },
    {
        key: 'greeting',
        what: 'Beste <contact>, or Geachte heer, mevrouw when there is no contact',
    },
    {
        key: 'opening',
        what: 'The opening sentence, adapting to a missing city or industry',
    },
    {
        key: 'previous_sent_at',
        what: 'Date the last letter went out, blank when nothing has been sent',
    },
];

const preview = computed(() => ({
    subject: render(form.subject),
    body: render(form.body),
    email_subject: render(form.email_subject),
    email_body: render(form.email_body),
}));
</script>

<template>
    <Head title="Letter template" />

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            title="Letter template"
            description="The letter and cover email every draft starts from, per type and per language. A company is written to in the language set on its record."
        />

        <div class="flex flex-wrap gap-2">
            <Button
                v-for="option in types"
                :key="option.value"
                :variant="option.value === type ? 'default' : 'outline'"
                size="sm"
                as-child
            >
                <Link
                    :href="
                        edit({
                            query: { type: option.value, language: language },
                        })
                    "
                >
                    {{ option.label }}
                </Link>
            </Button>
        </div>

        <div class="flex flex-wrap gap-2">
            <Button
                v-for="option in languages"
                :key="option.value"
                :variant="option.value === language ? 'secondary' : 'ghost'"
                size="sm"
                as-child
            >
                <Link
                    :href="
                        edit({ query: { type: type, language: option.value } })
                    "
                >
                    {{ option.label }}
                </Link>
            </Button>
        </div>

        <p class="text-sm text-muted-foreground">
            {{ activeType?.description }}
        </p>

        <div class="rounded-md border border-border p-4">
            <p class="text-sm font-medium">Placeholders</p>
            <dl class="mt-2 grid gap-1 text-sm sm:grid-cols-2">
                <div
                    v-for="placeholder in placeholders"
                    :key="placeholder.key"
                    class="flex gap-2"
                >
                    <dt class="font-mono text-xs whitespace-nowrap">
                        &#123;&#123; {{ placeholder.key }} &#125;&#125;
                    </dt>
                    <dd class="text-muted-foreground">
                        {{ placeholder.what }}
                    </dd>
                </div>
            </dl>
        </div>

        <form class="flex flex-col gap-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="subject">Letter subject</Label>
                <Input id="subject" v-model="form.subject" required />
                <InputError :message="form.errors.subject" />
            </div>

            <div class="grid gap-2">
                <Label for="body">Letter body</Label>
                <Textarea
                    id="body"
                    v-model="form.body"
                    class="min-h-[360px] font-mono text-sm"
                    required
                />
                <InputError :message="form.errors.body" />
            </div>

            <div class="grid gap-2">
                <Label for="email_subject">Email subject</Label>
                <Input
                    id="email_subject"
                    v-model="form.email_subject"
                    required
                />
                <InputError :message="form.errors.email_subject" />
            </div>

            <div class="grid gap-2">
                <Label for="email_body">Email body</Label>
                <Textarea
                    id="email_body"
                    v-model="form.email_body"
                    class="min-h-[220px] font-mono text-sm"
                    required
                />
                <InputError :message="form.errors.email_body" />
            </div>

            <div class="flex items-center gap-3">
                <Button type="submit" :disabled="form.processing">
                    Save template
                </Button>
                <Button type="button" variant="ghost" @click="restoreDefaults">
                    <RotateCcw />
                    Restore defaults
                </Button>
            </div>
        </form>

        <div class="flex flex-col gap-3 border-t pt-6">
            <Heading
                variant="small"
                title="Preview"
                description="Filled in for a sample company: Acme BV in Enschede, contact Jane Doe, industry softwareontwikkeling."
            />

            <div class="grid gap-1">
                <p class="text-sm text-muted-foreground">Letter subject</p>
                <p class="text-sm font-medium">{{ preview.subject }}</p>
            </div>

            <div class="grid gap-1">
                <p class="text-sm text-muted-foreground">Letter body</p>
                <p
                    class="rounded-md border border-border p-4 text-sm whitespace-pre-line"
                >
                    {{ preview.body }}
                </p>
            </div>

            <div class="grid gap-1">
                <p class="text-sm text-muted-foreground">Email subject</p>
                <p class="text-sm font-medium">{{ preview.email_subject }}</p>
            </div>

            <div class="grid gap-1">
                <p class="text-sm text-muted-foreground">Email body</p>
                <p
                    class="rounded-md border border-border p-4 text-sm whitespace-pre-line"
                >
                    {{ preview.email_body }}
                </p>
            </div>
        </div>
    </div>
</template>
