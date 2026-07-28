<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Download, FileText, Trash2 } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { destroy, download, edit, update } from '@/routes/cv';

defineProps<{
    cv: { name: string; size: number; uploaded_at: string } | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'CV settings',
                href: edit(),
            },
        ],
    },
});

const form = useForm<{ cv: File | null }>({ cv: null });

const onFile = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.cv = target.files?.[0] ?? null;
};

const submit = () => {
    form.post(update().url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => form.reset('cv'),
    });
};

const remove = () => {
    router.delete(destroy().url, { preserveScroll: true });
};

const formatSize = (bytes: number) => {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
};

const formatDate = (value: string) => new Date(value).toLocaleDateString();
</script>

<template>
    <Head title="CV settings" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="CV"
            description="Upload the CV attached to every outreach email."
        />

        <div
            v-if="cv"
            class="flex items-center justify-between gap-4 rounded-lg border p-4"
        >
            <div class="flex min-w-0 items-center gap-3">
                <FileText class="size-5 shrink-0 text-muted-foreground" />
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium">{{ cv.name }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ formatSize(cv.size) }} · uploaded
                        {{ formatDate(cv.uploaded_at) }}
                    </p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <Button variant="outline" size="sm" as-child>
                    <a :href="download().url">
                        <Download />
                        Download
                    </a>
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    class="text-destructive hover:text-destructive"
                    @click="remove"
                >
                    <Trash2 />
                    Remove
                </Button>
            </div>
        </div>

        <p v-else class="text-sm text-muted-foreground">No CV uploaded yet.</p>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="cv">{{ cv ? 'Replace CV' : 'Upload CV' }}</Label>
                <input
                    id="cv"
                    type="file"
                    accept="application/pdf"
                    class="block w-full text-sm text-muted-foreground file:mr-4 file:rounded-md file:border file:border-input file:bg-transparent file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-accent"
                    @change="onFile"
                />
                <p class="text-xs text-muted-foreground">PDF, up to 5 MB.</p>
                <InputError :message="form.errors.cv" />
            </div>

            <Button type="submit" :disabled="!form.cv || form.processing">
                {{ cv ? 'Replace' : 'Upload' }}
            </Button>
        </form>
    </div>
</template>
