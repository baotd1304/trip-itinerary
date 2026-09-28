<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { statusClass, statusKey } from '@/lib/trip-status';
import type { TripStatus } from '@/types/trip';

defineProps<{ status: TripStatus }>();
</script>

<template>
    <Badge class="inline-flex min-w-[88px] justify-center text-white" :class="statusClass(status)">
        {{ $t(statusKey(status)) }}
    </Badge>
</template>