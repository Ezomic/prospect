<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import type { BadgeVariants } from '@/components/ui/badge';
import type { CompanyStatus } from '@/types';

defineProps<{
    status: CompanyStatus;
}>();

const variants: Record<CompanyStatus, BadgeVariants['variant']> = {
    new: 'secondary',
    sent: 'default',
    replied: 'default',
    bounced: 'destructive',
    closed: 'outline',
};

const labels: Record<CompanyStatus, string> = {
    new: 'New',
    sent: 'Sent',
    replied: 'Replied',
    bounced: 'Bounced',
    closed: 'Closed',
};
</script>

<template>
    <Badge :variant="variants[status]">{{ labels[status] }}</Badge>
</template>
