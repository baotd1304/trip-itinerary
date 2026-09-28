<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useFormat } from '@/composables/useFormat';
import type { TripFormModel } from '@/types/trip';

const props = defineProps<{ model: TripFormModel; showEditHint?: boolean }>();
const { t } = useI18n();
const { formatDateTime } = useFormat();

const reviewerName = computed(() => props.model.reviewer?.name ?? t('trip.cell.fallbackAdvisor'));
const latest = computed(() => props.model.latest_reopen_request ?? null);

const approved = computed(() =>
    props.model.status === 'editing' && latest.value?.status === 'approved' ? latest.value : null);

const rejected = computed(() =>
    props.model.status === 'confirmed' && latest.value?.status === 'rejected' ? latest.value : null);

const pending = computed(() => props.model.pending_reopen_request ?? null);
</script>

<template>
    <div class="space-y-3">
        <!-- Cố vấn từ chối chuyến -->
        <div v-if="model.status === 'rejected' && model.reject_reason"
             class="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm
                    text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-200">
            <p class="font-semibold">{{ $t('trip.banners.rejectedTitle', { name: reviewerName }) }}</p>
            <p class="mt-1">{{ $t('common.reason') }}: {{ model.reject_reason }}</p>
            <p v-if="showEditHint" class="mt-2 text-xs opacity-80">
                {{ $t('trip.banners.rejectedEditHint') }}
            </p>
        </div>

        <!-- Yêu cầu mở khoá ĐƯỢC DUYỆT -->
        <div v-if="approved"
             class="rounded-md border border-indigo-300 bg-indigo-50 px-3 py-2 text-sm
                    text-indigo-900 dark:border-indigo-700 dark:bg-indigo-950 dark:text-indigo-100">
            <p class="font-semibold">
                {{ $t('trip.banners.reopenApprovedTitle', { name: approved.reviewer?.name ?? $t('trip.cell.fallbackAdvisor') }) }}
                <span v-if="approved.reviewed_at" class="font-normal opacity-75">
                    · {{ formatDateTime(approved.reviewed_at) }}
                </span>
            </p>
            <p v-if="approved.review_note" class="mt-1">
                <span class="font-medium">{{ $t('trip.banners.reviewNote') }}:</span> {{ approved.review_note }}
            </p>
            <p v-else class="mt-1 opacity-80">{{ $t('trip.banners.noReviewNote') }}</p>
            <p class="mt-2 border-t border-indigo-200 pt-2 text-xs dark:border-indigo-800">
                <span class="font-medium">{{ $t('trip.banners.requestReason') }}:</span> "{{ approved.reason }}"
            </p>
        </div>

        <!-- Yêu cầu mở khoá BỊ TỪ CHỐI -->
        <div v-if="rejected"
             class="rounded-md border border-orange-300 bg-orange-50 px-3 py-2 text-sm
                    text-orange-900 dark:border-orange-700 dark:bg-orange-950 dark:text-orange-100">
            <p class="font-semibold">
                {{ $t('trip.banners.reopenRejectedTitle', { name: rejected.reviewer?.name ?? $t('trip.cell.fallbackAdvisor') }) }}
                <span v-if="rejected.reviewed_at" class="font-normal opacity-75">
                    · {{ formatDateTime(rejected.reviewed_at) }}
                </span>
            </p>
            <p class="mt-1">
                <span class="font-medium">{{ $t('trip.banners.reviewNote') }}:</span> {{ rejected.review_note }}
            </p>
        </div>

        <!-- Đang CHỜ duyệt -->
        <div v-if="pending"
             class="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm
                    text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            <p class="font-semibold">{{ $t('trip.banners.reopenPendingTitle') }}</p>
            <p class="mt-1">
                <span class="font-medium">{{ pending.requester?.name }}:</span> "{{ pending.reason }}"
            </p>
            <p class="mt-1 text-xs opacity-75">
                {{ $t('common.sentAt') }}: {{ formatDateTime(pending.created_at) }}
            </p>
        </div>
    </div>
</template>