<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, FileDown, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { index as companiesIndex, show } from '@/routes/companies';
import { destroy, pdf, update } from '@/routes/letters';
import type { Letter, LetterStatus, LetterStatusOption } from '@/types';

const props = defineProps<{
    letter: Letter;
    statuses: LetterStatusOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Companies',
                href: companiesIndex(),
            },
            {
                title: 'Letter',
                href: companiesIndex(),
            },
        ],
    },
});

const form = useForm({
    subject: props.letter.subject,
    body: props.letter.body,
    status: props.letter.status as LetterStatus,
});

const submit = () => {
    form.put(update(props.letter.id).url, { preserveScroll: true });
};

const deleteOpen = ref(false);
const deleting = ref(false);

const confirmDelete = () => {
    router.delete(destroy(props.letter.id).url, {
        onStart: () => (deleting.value = true),
        onFinish: () => (deleting.value = false),
    });
};
</script>

<template>
    <Head title="Edit letter" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Letter"
                :description="letter.company?.name ?? 'Open-aanbod letter'"
            />
            <div class="flex items-center gap-2">
                <Button variant="outline" as-child>
                    <Link :href="show(letter.company_id)">
                        <ArrowLeft />
                        Back to company
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <a
                        :href="pdf(letter.id).url"
                        target="_blank"
                        rel="noopener"
                    >
                        <FileDown />
                        PDF
                    </a>
                </Button>
                <Button
                    variant="outline"
                    class="text-destructive hover:text-destructive"
                    @click="deleteOpen = true"
                >
                    <Trash2 />
                    Delete
                </Button>
            </div>
        </div>

        <form class="max-w-3xl space-y-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="subject">Subject</Label>
                <Input id="subject" v-model="form.subject" required />
                <InputError :message="form.errors.subject" />
            </div>

            <div class="grid gap-2 sm:max-w-xs">
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

            <div class="grid gap-2">
                <Label for="body">Body</Label>
                <Textarea
                    id="body"
                    v-model="form.body"
                    class="min-h-[420px] font-mono text-sm"
                    required
                />
                <InputError :message="form.errors.body" />
            </div>

            <div class="flex items-center gap-3">
                <Button type="submit" :disabled="form.processing">
                    Save letter
                </Button>
                <Button variant="ghost" as-child>
                    <Link :href="show(letter.company_id)">Cancel</Link>
                </Button>
            </div>
        </form>
    </div>

    <Dialog v-model:open="deleteOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete letter?</DialogTitle>
                <DialogDescription>
                    This permanently removes this letter. This cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancel</Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    :disabled="deleting"
                    @click="confirmDelete"
                >
                    Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
