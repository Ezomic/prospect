<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, TriangleAlert, Upload } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { index as companiesIndex } from '@/routes/companies';
import { preview as previewRoute, store } from '@/routes/companies/import';

type ImportRow = {
    line: number;
    values: Record<string, string | null>;
    errors: Record<string, string>;
    duplicate: { name: string; matched_on: string } | null;
};

type Preview = {
    rows: ImportRow[];
    recognised: string[];
    ignored: string[];
};

const props = defineProps<{
    csv: string | null;
    preview: Preview | null;
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

const example =
    'name,email,city,industry,source\nAcme BV,info@acme.example,Enschede,Software,directory';

const form = useForm({ csv: props.csv ?? '' });

const submitPreview = () => {
    form.post(previewRoute().url, { preserveScroll: true });
};

const hasError = (row: ImportRow) => Object.keys(row.errors).length > 0;

// A row that duplicates something we already have starts unticked: the point
// of the preview is to not import it by accident.
const selected = ref<number[]>([]);

const resetSelection = () => {
    selected.value = (props.preview?.rows ?? [])
        .filter((row) => !hasError(row) && row.duplicate === null)
        .map((row) => row.line);
};

resetSelection();
watch(() => props.preview, resetSelection);

const toggle = (line: number, checked: boolean) => {
    selected.value = checked
        ? [...selected.value, line]
        : selected.value.filter((value) => value !== line);
};

const importable = computed(
    () => props.preview?.rows.filter((row) => !hasError(row)) ?? [],
);

const invalidCount = computed(
    () => props.preview?.rows.filter(hasError).length ?? 0,
);

const duplicateCount = computed(
    () =>
        props.preview?.rows.filter((row) => row.duplicate !== null).length ?? 0,
);

const importing = ref(false);

const confirmImport = () => {
    router.post(
        store().url,
        { csv: props.csv ?? '', lines: selected.value },
        {
            onStart: () => (importing.value = true),
            onFinish: () => (importing.value = false),
        },
    );
};

const columns = [
    'name',
    'email',
    'contact_name',
    'city',
    'kvk_number',
    'industry',
    'source',
];
</script>

<template>
    <Head title="Import companies" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Import companies"
                description="Paste CSV with a header row. Nothing is written until you confirm the preview."
            />
            <Button variant="outline" as-child>
                <Link :href="companiesIndex()">
                    <ArrowLeft />
                    Back
                </Link>
            </Button>
        </div>

        <form
            class="flex max-w-4xl flex-col gap-3"
            @submit.prevent="submitPreview"
        >
            <Label for="csv">CSV</Label>
            <Textarea
                id="csv"
                v-model="form.csv"
                class="min-h-[220px] font-mono text-sm"
                :placeholder="example"
                required
            />
            <InputError :message="form.errors.csv" />
            <p class="text-sm text-muted-foreground">
                Recognised columns: name (required), website, email,
                contact_name, contact_role, city, kvk_number, industry, source.
                Comma or semicolon separated.
            </p>
            <div>
                <Button type="submit" :disabled="form.processing">
                    Preview
                </Button>
            </div>
        </form>

        <template v-if="preview">
            <div class="flex flex-col gap-2">
                <h2 class="text-sm font-medium">
                    Preview: {{ preview.rows.length }}
                    {{ preview.rows.length === 1 ? 'row' : 'rows' }}
                </h2>
                <p class="text-sm text-muted-foreground">
                    {{ importable.length }} importable, {{ invalidCount }} with
                    errors, {{ duplicateCount }} looking like duplicates
                    (unticked by default).
                </p>
                <p
                    v-if="preview.ignored.length > 0"
                    class="text-sm text-muted-foreground"
                >
                    Ignored columns: {{ preview.ignored.join(', ') }}
                </p>
            </div>

            <div
                v-if="preview.rows.length > 0"
                class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-sidebar-border/70 text-left text-muted-foreground dark:border-sidebar-border"
                        >
                            <th class="px-4 py-3 font-medium">Import</th>
                            <th class="px-4 py-3 font-medium">Line</th>
                            <th
                                v-for="column in columns"
                                :key="column"
                                class="px-4 py-3 font-medium"
                            >
                                {{ column }}
                            </th>
                            <th class="px-4 py-3 font-medium">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in preview.rows"
                            :key="row.line"
                            class="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
                            :class="{ 'opacity-60': hasError(row) }"
                        >
                            <td class="px-4 py-3">
                                <Checkbox
                                    :model-value="selected.includes(row.line)"
                                    :disabled="hasError(row)"
                                    :aria-label="`Import line ${row.line}`"
                                    @update:model-value="
                                        (checked) =>
                                            toggle(row.line, checked === true)
                                    "
                                />
                            </td>
                            <td
                                class="px-4 py-3 text-muted-foreground tabular-nums"
                            >
                                {{ row.line }}
                            </td>
                            <td
                                v-for="column in columns"
                                :key="column"
                                class="px-4 py-3"
                                :class="
                                    row.errors[column]
                                        ? 'text-destructive'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ row.values[column] ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <p
                                    v-for="(message, field) in row.errors"
                                    :key="field"
                                    class="text-destructive"
                                >
                                    {{ message }}
                                </p>
                                <p
                                    v-if="row.duplicate"
                                    class="flex items-center gap-1 text-amber-600 dark:text-amber-500"
                                >
                                    <TriangleAlert class="size-3.5 shrink-0" />
                                    Matches {{ row.duplicate.name }} on
                                    {{ row.duplicate.matched_on }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center gap-3">
                <Button
                    :disabled="importing || selected.length === 0"
                    @click="confirmImport"
                >
                    <Upload />
                    Import {{ selected.length }}
                    {{ selected.length === 1 ? 'company' : 'companies' }}
                </Button>
                <Button variant="ghost" as-child>
                    <Link :href="companiesIndex()">Cancel</Link>
                </Button>
            </div>
        </template>
    </div>
</template>
