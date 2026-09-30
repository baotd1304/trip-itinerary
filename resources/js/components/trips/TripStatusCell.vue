<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import TripStatusBadge from './TripStatusBadge.vue';
import { approvedReopen, rejectedReopen } from '@/lib/trip-status';
import { truncate } from '@/lib/array';
import type { Trip } from '@/types/trip';

const props = defineProps<{ trip: Trip }>();
const { t } = useI18n();

const reviewerName = computed(() => props.trip.reviewer?.name ?? t('trip.cell.fallbackAdvisor'));

const approvedNote = computed(() => {
    if (props.trip.status !== 'editing') return null;
    const r = approvedReopen(props.trip);
    if (!r) return null;
    return r.review_note?.trim() || t('trip.banners.defaultApproveNote');
});
</script>

<template>
    <div class="space-y-1">
        <TripStatusBadge :status="trip.status" />

        <!-- Bị từ chối -->
        <p v-if="trip.status === 'rejected' && trip.reject_reason"
           class="mx-auto max-w-[240px] text-left text-xs text-red-600 dark:text-red-400"
           :title="trip.reject_reason">
            <span class="font-medium">{{ $t('trip.cell.rejectedBy', { name: reviewerName }) }}</span>
            {{ truncate(trip.reject_reason) }}
        </p>

        <!-- Chờ duyệt mở khoá -->
        <p v-if="trip.pending_reopen_request"
           class="mx-auto max-w-[240px] text-left text-xs text-amber-600 dark:text-amber-400"
           :title="trip.pending_reopen_request.reason">
            <span class="font-medium">{{ $t('trip.cell.waitingReopen') }}</span>
            {{ truncate(trip.pending_reopen_request.reason) }}
        </p>

        <!-- Đã được duyệt mở khoá -->
        <p v-if="approvedNote"
           class="mx-auto max-w-[240px] text-left text-xs text-indigo-600 dark:text-indigo-400"
           :title="approvedNote">
            <span class="font-medium">{{ $t('trip.cell.reopenApproved') }}</span>
            {{ truncate(approvedNote) }}
        </p>

        <!-- Yêu cầu mở khoá bị từ chối -->
        <p v-if="trip.status === 'confirmed' && rejectedReopen(trip)"
           class="mx-auto max-w-[240px] text-left text-xs text-orange-600 dark:text-orange-400"
           :title="rejectedReopen(trip)?.review_note ?? ''">
            <span class="font-medium">{{ $t('trip.cell.reopenRejected') }}</span>
            {{ truncate(rejectedReopen(trip)?.review_note) }}
        </p>
    </div>
</template>