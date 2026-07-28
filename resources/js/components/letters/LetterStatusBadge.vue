<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import type { BadgeVariants } from '@/components/ui/badge';
import type { LetterStatus } from '@/types';

defineProps<{
    status: LetterStatus;
}>();

const variants: Record<LetterStatus, BadgeVariants['variant']> = {
    draft: 'secondary',
    ready: 'default',
    sent: 'outline',
};

const labels: Record<LetterStatus, string> = {
    draft: 'Draft',
    ready: 'Ready',
    sent: 'Sent',
};
</script>

<template>
    <Badge :variant="variants[status]">{{ labels[status] }}</Badge>
</template>
