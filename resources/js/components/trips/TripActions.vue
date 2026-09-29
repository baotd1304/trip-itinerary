<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Ban, CheckCircle2, Eye, Loader2, Pencil, ThumbsDown, ThumbsUp, Trash2, Unlock,
} from 'lucide-vue-next';
import {
    canDelete, canRequestReopen, canReview, canReviewReopen, canUpdate,
    deleteHintKey, editLabelKey, updateHintKey,
} from '@/lib/trip-permissions';
import type { Trip, TripActionType } from '@/types/trip';

const props = withDefaults(defineProps<{
    trip: Trip;
    variant?: 'table' | 'dialog';
    confirming?: boolean;
    showEmptyHint?: boolean;
    // giới hạn nút được render:
    // - null/undefined => hiển thị tất cả các nút mà user có quyền
    // - [view]         => chỉ hiển thị nút View
    only?: TripActionType[] | null;
}>(), {
    variant: 'table',
    confirming: false,
    showEmptyHint: false,
    only: null,
});

const emit = defineEmits<{
    (e: 'action', payload: { type: TripActionType; trip: Trip }): void;
}>();
const fire = (type: TripActionType) => emit('action', { type, trip: props.trip });

/** Kiểm tra 1 hành động có nằm trong whitelist hay không */
const allow = (type: TripActionType) => !props.only || props.only.includes(type);

/* Gom điều kiện hiển thị để template gọn và dùng lại cho empty-hint */
const showView = computed(() => allow('view'));
const showEdit = computed(() => allow('edit') && canUpdate(props.trip));
const showRequestReopen = computed(() => allow('request-reopen') && canRequestReopen(props.trip));
const showDelete = computed(() => allow('delete') && canDelete(props.trip));

const showReopenReview = computed(() => canReviewReopen(props.trip));
const showApproveReopen = computed(() => allow('approve-reopen') && showReopenReview.value);
const showRejectReopen = computed(() => allow('reject-reopen') && showReopenReview.value);

const showConfirm = computed(() => allow('confirm') && canReview(props.trip));
const showReject = computed(() => allow('reject') && canReview(props.trip));

const hasAnyAction = computed(() =>
    showView.value || showEdit.value || showRequestReopen.value || showDelete.value
    || showApproveReopen.value || showRejectReopen.value
    || showConfirm.value || showReject.value,
);
</script>

<template>
    <div class="flex flex-wrap gap-1.5" :class="variant === 'table' ? 'justify-center' : 'justify-start' ">
        <!-- xem chi tiết -->
        <Button v-if="showView" size="sm" variant="outline"
                :title="$t('trip.actions.view')" @click="fire('view')">
            <Eye class="h-4 w-4" />
        </Button>
        <!-- update -->
        <Button v-if="showEdit" size="sm" variant="outline"
                :title="$t(updateHintKey(trip))" @click="fire('edit')">
            <Pencil class="mr-1 h-3.5 w-3.5" /> {{ $t(editLabelKey(trip)) }}
        </Button>
        <!-- yêu cầu mở khóa -->
        <Button v-if="showRequestReopen" size="sm" variant="secondary"
                :title="$t('trip.hints.requestReopen')" @click="fire('request-reopen')">
            <Unlock class="mr-1 h-3.5 w-3.5" /> {{ $t('trip.actions.requestReopen') }}
        </Button>
        <!-- xóa -->
        <Button v-if="showDelete" size="sm" variant="destructive"
                :title="$t(deleteHintKey(trip))" @click="fire('delete')">
            <Trash2 class="mr-1 h-3.5 w-3.5" /> {{ $t('trip.actions.delete') }}
        </Button>

        <!-- Cố vấn / Admin: duyệt/từ chối yêu cầu mở khoá -->
        <template v-if="showApproveReopen || showRejectReopen">
            <Button size="sm" variant="primary"
                    :title="$t('trip.hints.approveReopen')" @click="fire('approve-reopen')">
                <ThumbsUp class="mr-1 h-3.5 w-3.5" /> {{ $t('trip.actions.approveReopen') }}
            </Button>
            <Button size="sm" variant="default"
                    :title="$t('trip.hints.rejectReopen')" @click="fire('reject-reopen')">
                <ThumbsDown class="mr-1 h-3.5 w-3.5" /> {{ $t('trip.actions.rejectReopen') }}
            </Button>
        </template>

        <!-- Cố vấn / Admin: xác nhận / từ chối chuyến -->
        <template v-if="showConfirm || showReject">
            <Button size="sm" variant="success" :disabled="confirming"
                    :title="$t('trip.hints.confirm')" @click="fire('confirm')">
                <Loader2 v-if="confirming" class="mr-1 h-3.5 w-3.5 animate-spin" />
                <CheckCircle2 v-else class="mr-1 h-3.5 w-3.5" />
                {{ $t('trip.actions.confirm') }}
            </Button>
            <Button size="sm" variant="default"
                    :title="$t('trip.hints.reject')" @click="fire('reject')">
                <Ban class="mr-1 h-3.5 w-3.5" /> {{ $t('trip.actions.reject') }}
            </Button>
        </template>

        <p v-if="showEmptyHint && !hasAnyAction"
           class="self-center text-xs text-muted-foreground">
            {{ $t('trip.actions.noneAvailable') }}
        </p>
    </div>
</template>