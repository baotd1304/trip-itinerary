<script setup lang="ts">
import {
    Ban, CheckCheck, CheckCircle2, CircleX, Eye, Loader2, 
    MoreHorizontal, Pencil, ThumbsDown, ThumbsUp, Trash2, Unlock, 
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import {
    canDelete, canRequestReopen, canReview, canReviewReopen, canUpdate,
    deleteHintKey, editLabelKey, updateHintKey,
} from '@/lib/trip-permissions';
import type { Trip, TripActionType } from '@/types/trip';

const props = withDefaults(defineProps<{
    trip?: Trip;
    trips?: Trip[];
    variant?: 'table' | 'dialog' | 'bulk' | 'bulk-checkbox';
    confirming?: boolean;
    showEmptyHint?: boolean;
    bulkReview?: boolean;
    bulkProcessing?: boolean;
    checkboxValue?: boolean | 'indeterminate';
    checkboxDisabled?: boolean;
    checkboxLabel?: string;
    // giới hạn nút được render:
    // - null/undefined => hiển thị tất cả các nút mà user có quyền
    // - [view]         => chỉ hiển thị nút View
    only?: TripActionType[] | null;
}>(), {
    variant: 'table',
    confirming: false,
    showEmptyHint: false,
    bulkReview: false,
    bulkProcessing: false,
    checkboxValue: false,
    checkboxDisabled: false,
    checkboxLabel: '',
    only: null,
});

const emit = defineEmits<{
    (e: 'action', payload: { type: TripActionType; trip: Trip }): void;
    (e: 'bulk-confirm', tripIds: number[]): void;
    (e: 'bulk-reject', tripIds: number[]): void;
    (e: 'select', value: boolean | 'indeterminate'): void;
}>();
const fire = (type: TripActionType) => {
    if (props.trip) {
        emit('action', { type, trip: props.trip });
    }
};

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
const variantIsTable = computed(() => props.variant === 'table');

const showConfirm = computed(() => allow('confirm') && canReview(props.trip));
const showReject = computed(() => allow('reject') && canReview(props.trip));
const showQuickReview = computed(() =>
    variantIsTable.value && (showConfirm.value || showReject.value),
);

const selectedTripIds = ref(new Set<number>());
const canBulkReview = (trip: Trip): boolean => trip.status === 'pending' && canReview(trip);
const reviewableTrips = computed(() => (props.trips ?? []).filter(canBulkReview));
const reviewableTripIds = computed(() => new Set(reviewableTrips.value.map((trip) => trip.id)));
const selectedTrips = computed(() =>
    reviewableTrips.value.filter((trip) => selectedTripIds.value.has(trip.id)),
);
const allReviewableSelected = computed(() =>
    reviewableTrips.value.length > 0 && selectedTrips.value.length === reviewableTrips.value.length,
);

watch(
    () => (props.trips ?? []).map((trip) => ({ id: trip.id, reviewable: canBulkReview(trip) })),
    (trips) => {
        const reviewableIds = new Set(trips.filter((trip) => trip.reviewable).map((trip) => trip.id));
        selectedTripIds.value = new Set(
            [...selectedTripIds.value].filter((id) => reviewableIds.has(id)),
        );
    },
);

const setTripSelected = (tripId: number, checked: boolean | 'indeterminate') => {
    const updatedIds = new Set(selectedTripIds.value);

    if (checked === true) {
        updatedIds.add(tripId);
    } else {
        updatedIds.delete(tripId);
    }

    selectedTripIds.value = updatedIds;
};

const setAllTripsSelected = (checked: boolean | 'indeterminate') => {
    selectedTripIds.value = checked === true
        ? new Set(reviewableTrips.value.map((trip) => trip.id))
        : new Set();
};

const emitBulkConfirm = () => emit('bulk-confirm', selectedTrips.value.map((trip) => trip.id));
const emitBulkReject = () => emit('bulk-reject', selectedTrips.value.map((trip) => trip.id));

const hasAnyAction = computed(() =>
    showView.value || showEdit.value || showRequestReopen.value || showDelete.value
    || showApproveReopen.value || showRejectReopen.value
    || showConfirm.value || showReject.value,
);
</script>

<template>
    <div v-if="variant === 'bulk'" class="space-y-3">
        <div class="flex flex-wrap justify-end">
            <div class="flex w-full flex-wrap items-center justify-end gap-2">
                <div v-if="bulkReview && selectedTrips.length" class="flex flex-wrap items-center justify-end gap-1.5 sm:gap-2">
                    <span class="text-xs text-muted-foreground sm:text-sm">
                        {{ $t('trip.bulkReview.selected', { count: selectedTrips.length }) }}
                    </span>
                    <Button
                        type="button"
                        size="xs"
                        variant="success"
                        class="h-9 w-9 gap-1 px-0 text-xs sm:h-8 sm:w-auto sm:px-2.5"
                        :disabled="bulkProcessing"
                        :title="$t('trip.bulkReview.confirm')"
                        :aria-label="$t('trip.bulkReview.confirm')"
                        @click="emitBulkConfirm"
                    >
                        <CheckCheck class="size-3.5 shrink-0" />
                        <span class="hidden sm:inline">{{ $t('trip.bulkReview.confirm') }}</span>
                    </Button>
                    <Button
                        type="button"
                        size="xs"
                        variant="destructive"
                        class="h-9 w-9 gap-1 px-0 text-xs sm:h-8 sm:w-auto sm:px-2.5"
                        :disabled="bulkProcessing"
                        :title="$t('trip.bulkReview.reject')"
                        :aria-label="$t('trip.bulkReview.reject')"
                        @click="emitBulkReject"
                    >
                        <CircleX class="size-3.5 shrink-0" />
                        <span class="hidden sm:inline">{{ $t('trip.bulkReview.reject') }}</span>
                    </Button>
                </div>
                <slot name="toolbar" />
            </div>
        </div>
        <slot
            :selected-trip-ids="selectedTripIds"
            :reviewable-trip-ids="reviewableTripIds"
            :selected-trips="selectedTrips"
            :all-reviewable-selected="allReviewableSelected"
            :set-trip-selected="setTripSelected"
            :set-all-trips-selected="setAllTripsSelected"
        />
    </div>

    <div v-else-if="trip" class="flex gap-1.5" :class="variant === 'table' ? 'flex-nowrap justify-start' : 'flex-wrap justify-start'">
        <!-- Duyệt nhanh trên bảng, chỉ hiện các hành động user được phép thực hiện -->
        <Popover v-if="showQuickReview">
            <PopoverTrigger as-child>
                <Button
                    type="button"
                    variant="outline"
                    size="xs"
                    :aria-label="$t('trip.actions.confirm') + ' / ' + $t('trip.actions.reject')"
                    :title="$t('trip.actions.confirm') + ' / ' + $t('trip.actions.reject')"
                >
                    <MoreHorizontal class="h-4 w-4" />
                </Button>
            </PopoverTrigger>
            <PopoverContent align="end" :side-offset="6" class="w-25 p-2">
                <div class="flex flex-col gap-2">
                    <Button v-if="showConfirm" type="button" class="w-full" size="xs" variant="success"
                            :disabled="confirming" :title="$t('trip.hints.confirm')" @click="fire('confirm')">
                        <Loader2 v-if="confirming" class="mr-1 h-3.5 w-3.5 animate-spin" />
                        <CheckCircle2 v-else class="mr-1 h-3.5 w-3.5" />
                        {{ $t('trip.actions.confirm') }}
                    </Button>
                    <Button v-if="showReject" type="button" class="w-full" size="xs" variant="destructive"
                            :title="$t('trip.hints.reject')" @click="fire('reject')">
                        <Ban class="mr-1 h-3.5 w-3.5" />
                        {{ $t('trip.actions.reject') }}
                    </Button>
                </div>
            </PopoverContent>
        </Popover>
        <!-- <template v-if="showQuickReview">
            <Button v-if="showConfirm" size="xs" variant="success" :disabled="confirming"
                    :title="$t('trip.hints.confirm')" @click="fire('confirm')">
                <Loader2 v-if="confirming" class="mr-1 h-3.5 w-3.5 animate-spin" />
                <CheckCircle2 v-else class="mr-1 h-3.5 w-3.5" />
            </Button>
            <Button v-if="showReject" size="xs" variant="destructive"
                    :title="$t('trip.hints.reject')" @click="fire('reject')">
                <Ban class="mr-1 h-3.5 w-3.5" /> 
            </Button>
        </template> -->


        <!-- xem chi tiết -->
        <Button v-if="showView" size="xs" variant="outline"
                :title="$t('trip.actions.view')" @click="fire('view')">
            <Eye class="h-4 w-4" />
        </Button>
        <!-- update -->
        <Button v-if="showEdit" size="xs" variant="default"
                :title="$t(updateHintKey(trip))" @click="fire('edit')">
            <Pencil class="mr-1 h-3.5 w-3.5" /> {{ $t(editLabelKey(trip)) }}
        </Button>
        <!-- yêu cầu mở khóa -->
        <Button v-if="showRequestReopen" size="xs" variant="secondary"
                :title="$t('trip.hints.requestReopen')" @click="fire('request-reopen')">
            <Unlock class="mr-1 h-3.5 w-3.5" /> {{ $t('trip.actions.requestReopen') }}
        </Button>
        <!-- xóa -->
        <Button v-if="showDelete" size="xs" variant="destructive"
                :title="$t(deleteHintKey(trip))" @click="fire('delete')">
            <Trash2 class="mr-1 h-3.5 w-3.5" /> {{ $t('trip.actions.delete') }}
        </Button>

        <!-- Cố vấn / Admin: duyệt/từ chối yêu cầu mở khoá -->
        <template v-if="showApproveReopen || showRejectReopen">
            <Button size="xs" variant="primary"
                    :title="$t('trip.hints.approveReopen')" @click="fire('approve-reopen')">
                <ThumbsUp class="mr-1 h-3.5 w-3.5" /> {{ $t('trip.actions.approveReopen') }}
            </Button>
            <Button size="xs" variant="default"
                    :title="$t('trip.hints.rejectReopen')" @click="fire('reject-reopen')">
                <ThumbsDown class="mr-1 h-3.5 w-3.5" /> {{ $t('trip.actions.rejectReopen') }}
            </Button>
        </template>

        <!-- Cố vấn / Admin: xác nhận / từ chối chuyến trong dialog -->
        <template v-if="!variantIsTable && (showConfirm || showReject)">
            <Button v-if="showConfirm" size="xs" variant="success" :disabled="confirming"
                    :title="$t('trip.hints.confirm')" @click="fire('confirm')">
                <Loader2 v-if="confirming" class="mr-1 h-3.5 w-3.5 animate-spin" />
                <CheckCircle2 v-else class="mr-1 h-3.5 w-3.5" />
                {{ $t('trip.actions.confirm') }}
            </Button>
            <Button v-if="showReject" size="xs" variant="destructive"
                    :title="$t('trip.hints.reject')" @click="fire('reject')">
                <Ban class="mr-1 h-3.5 w-3.5" /> 
                {{ $t('trip.actions.reject') }}
            </Button>
        </template>
        

        <p v-if="showEmptyHint && !hasAnyAction"
           class="self-center text-xs text-muted-foreground">
            {{ $t('trip.actions.noneAvailable') }}
        </p>
    </div>

    <Checkbox
        v-else-if="variant === 'bulk-checkbox'"
        :model-value="checkboxValue"
        :disabled="checkboxDisabled"
        :aria-label="checkboxLabel"
        @update:model-value="emit('select', $event)"
    />
</template>