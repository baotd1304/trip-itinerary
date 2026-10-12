<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { CheckCheck, CircleCheckBigIcon, CircleX } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import TripActions from './TripActions.vue';
import TripStatusCell from './TripStatusCell.vue';
import TripColumnPicker from './TripColumnPicker.vue';
import { useFormat } from '@/composables/useFormat';
import { useTableColumns } from '@/composables/useTableColumns';
import { useStickyShadow } from '@/composables/useStickyShadow';
import { TABLE_ONLY_VIEW } from '@/lib/trip-action-presets';
import { canReview } from '@/lib/trip-permissions';
import { TRIP_TABLE_COLUMNS, DEFAULT_TRIP_TABLE_COLUMNS } from '@/types/trip-table';
import type { ColumnKey } from '@/types/trip-table';
import type { Trip, TripActionType } from '@/types/trip';

const props = withDefaults(
  defineProps<{
    trips: Trip[];
    confirmingId?: number | null;
    startIndex?: number;    // Số thứ tự bắt đầu STT (lấy từ meta.from của paginator, mặc định 1)
    storageKey?: string;
    defaultColumns?: ColumnKey[];
    tableActions?: TripActionType[];
    bulkReview?: boolean;
    bulkProcessing?: boolean;
  }>(),
  {
    confirmingId: null,
    startIndex: 1,
    storageKey: 'trip-table-visible-columns',
    defaultColumns: () => DEFAULT_TRIP_TABLE_COLUMNS,
    tableActions: () => TABLE_ONLY_VIEW,
    bulkReview: false,
    bulkProcessing: false,
  },
);

const emit = defineEmits<{
    (e: 'action', payload: { type: TripActionType; trip: Trip }): void;
    (e: 'bulk-confirm', tripIds: number[]): void;
    (e: 'bulk-reject', tripIds: number[]): void;
}>();

const { formatDate, formatDateTime, formatMoney } = useFormat();

// Visible column
const {
    renderedColumns,
    visibleColumnCount,
    optionalColumns,
    selectedOptionalCount,
    allOptionalSelected,
    isVisible,
    toggle,
    toggleAll,
    resetToDefault,
} = useTableColumns({
    columns: TRIP_TABLE_COLUMNS,
    storageKey: props.storageKey,
    defaultColumns: props.defaultColumns,
});
//Ghim column khi scroll
const scroller = ref<HTMLElement | null>(null);
const { atStart, atEnd } = useStickyShadow(scroller);
const selectedTripIds = ref(new Set<number>());

const canBulkReview = (trip: Trip): boolean => trip.status === 'pending' && canReview(trip);
const reviewableTrips = computed(() => props.trips.filter(canBulkReview));
const selectedTrips = computed(() =>
    reviewableTrips.value.filter((trip) => selectedTripIds.value.has(trip.id)),
);
const allReviewableSelected = computed(
    () =>
        reviewableTrips.value.length > 0
        && selectedTrips.value.length === reviewableTrips.value.length,
);

watch(
    () => props.trips.map((trip) => ({ id: trip.id, reviewable: canBulkReview(trip) })),
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

/** Chỉ ghim khi cột đó thực sự đang hiển thị */
const pinLeft = computed(() => isVisible('stt'));
const pinRight = computed(() => isVisible('actions'));

/* ---- Lớp dùng lại cho ô ghim ---- */
const stickyLeftCell = computed(() => [
    'sticky left-0 z-20 bg-white dark:bg-gray-950',
    !atStart.value
        ? 'shadow-[6px_0_6px_-6px_rgba(0,0,0,0.25)]'
        : '',
]);

const stickyRightCell = computed(() => [
    'sticky right-0 z-20 bg-white dark:bg-gray-950',
    !atEnd.value
        ? 'shadow-[-6px_0_6px_-6px_rgba(0,0,0,0.25)]'
        : '',
]);

const stickyLeftHead = computed(() => [
    'sticky left-0 top-0 z-40 bg-gray-100 dark:bg-gray-800',
    !atStart.value
        ? 'shadow-[6px_0_6px_-6px_rgba(0,0,0,0.25)]'
        : '',
]);

const stickyRightHead = computed(() => [
    'sticky right-0 top-0 z-40 bg-gray-100 dark:bg-gray-800',
    !atEnd.value
        ? 'shadow-[-6px_0_6px_-6px_rgba(0,0,0,0.25)]'
        : '',
]);
/** Viền chuẩn cho mọi ô (thay cho border-collapse) */
const cellBorder = 'border-b border-r border-gray-300 dark:border-gray-700';


/** Chuẩn hoá "HH:mm" từ chuỗi giờ hoặc datetime */
const hhmm = (v?: string | null): string => (v ? String(v).slice(0, 5) : '');

/** Hiển thị "07:30 → 11:45", nếu thiếu thì trả về phần có sẵn */
const timeRange = (trip: Trip): string => {
  const a = hhmm(trip.departure_time);
  const b = hhmm(trip.arrival_time);
  if (a && b) return `${a} → ${b}`;
  return a || b || '—';
};

const fire = (type: TripActionType, trip: Trip) => emit('action', { type, trip });
</script>

<template>
    <div class="space-y-3">
        <div class="flex flex-wrap justify-end">
            <div class="flex w-full flex-wrap items-center justify-end gap-2">
                <div v-if="bulkReview && selectedTrips.length" class="flex flex-wrap items-center justify-end gap-1.5 sm:gap-2">
                    <span class="text-xs text-muted-foreground sm:text-sm">
                        {{ $t('trip.bulkReview.selected', { count: selectedTrips.length }) }}
                    </span>
                    <Button
                        type="button"
                        size="sm"
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
                        size="sm"
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
                <TripColumnPicker
                    :optional-columns="optionalColumns"
                    :visible-column-count="visibleColumnCount"
                    :total-column-count="TRIP_TABLE_COLUMNS.length"
                    :selected-optional-count="selectedOptionalCount"
                    :all-selected="allOptionalSelected"
                    :is-visible="isVisible"
                    @toggle="toggle"
                    @toggle-all="toggleAll"
                    @reset="resetToDefault"
                />
            </div>
        </div>
        <!-- Scroller -->
        <div
            ref="scroller"
            class="relative max-h-[70vh] max-w-full overflow-x-auto rounded-lg border border-gray-300 dark:border-gray-700"
        >
            <table class="w-full border-separate border-spacing-0">
                <thead class="sticky top-0 z-30">
                    <tr class="bg-gray-100 dark:bg-gray-800">
                        <th
                            v-for="column in renderedColumns"
                            :key="column.key"
                            scope="col"
                            class="whitespace-nowrap border-b border-r border-gray-300 bg-gray-100 px-2 py-2 text-center align-middle text-sm font-semibold dark:border-gray-700 dark:bg-gray-800"
                            :class="[
                                cellBorder,
                                column.key === 'actions' ? 'w-px min-w-max whitespace-nowrap' : '',
                                column.key === 'stt' && pinLeft
                                    ? stickyLeftHead
                                    : '',
                                column.key === 'actions' && pinRight
                                    ? stickyRightHead
                                    : '',
                            ]"
                        >
                            <div v-if="bulkReview && column.key === 'actions'" class="flex min-w-max items-center justify-center gap-2 whitespace-nowrap">
                                <span>{{ $t(column.labelKey) }}</span>
                                <Checkbox
                                    :model-value="allReviewableSelected ? true : selectedTrips.length ? 'indeterminate' : false"
                                    :aria-label="$t('trip.bulkReview.selectAll')"
                                    :disabled="!reviewableTrips.length"
                                    @update:model-value="setAllTripsSelected"
                                />
                            </div>
                            <template v-else>{{ $t(column.labelKey) }}</template>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-if="!trips.length">
                        <td :colspan="visibleColumnCount"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            {{ $t('common.noData') }}
                        </td>
                    </tr>

                    <tr v-for="(trip, index) in trips" :key="trip.id" class="bg-white transition-colors hover:bg-gray-50 dark:bg-gray-950 dark:hover:bg-gray-900">
                        <td
                            v-if="isVisible('stt')"
                            class="sticky left-0 bg-inherit border px-2 py-2 text-center align-middle text-sm font-medium dark:border-gray-700"
                            :class="[cellBorder, stickyLeftCell]"
                        >
                            <button
                                type="button"
                                class="inline-flex min-w-8 items-center justify-center rounded-md px-2 py-1 text-primary underline-offset-4 transition-colors hover:bg-primary/10 hover:underline focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1"
                                :title="$t('trip.actions.viewNumber', { id: trip.id })"
                                :aria-label="$t('trip.actions.viewNumber', { id: trip.id })"
                                @click="fire('view', trip)"
                            >
                                {{ startIndex + index }}
                            </button>
                        </td>
                        <td v-if="isVisible('day')" class="whitespace-nowrap border px-2 py-2 text-center align-middle dark:border-gray-700">
                            {{ formatDate(trip.day) }}
                        </td>
                        <td v-if="isVisible('advisor')" class="border px-2 py-2 text-center align-middle dark:border-gray-700">
                            {{ trip.advisor?.name ?? '—' }}
                        </td>
                        <td v-if="isVisible('driver')" class="border px-2 py-2 text-center align-middle dark:border-gray-700">
                            {{ trip.driver?.name ?? '—' }}
                        </td>
                        <td v-if="isVisible('car')" class="whitespace-nowrap border px-2 py-2 text-center align-middle dark:border-gray-700">
                            {{ trip.car?.license_plate ?? '—' }}
                        </td>
                        <td
                            v-if="isVisible('origin')"
                            class="max-w-[150px] truncate border px-2 py-2 text-center align-middle dark:border-gray-700"
                            :title="trip.origin"
                        >
                            {{ trip.origin }}
                        </td>
                        <td
                            v-if="isVisible('destination')"
                            class="max-w-[150px] truncate border px-2 py-2 text-center align-middle dark:border-gray-700"
                            :title="trip.destination"
                        >
                            {{ trip.destination }}
                        </td>
                        

                        <td v-if="isVisible('time')" class="whitespace-nowrap border px-2 py-2 text-center align-middle text-sm dark:border-gray-700">
                            {{ timeRange(trip) }}
                        </td>

                        <td v-if="isVisible('distance')" class="border px-2 py-2 text-center align-middle dark:border-gray-700">
                            {{ trip.distance }}
                        </td>

                        <td v-if="isVisible('status')" class="border px-2 py-2 text-center align-middle dark:border-gray-700">
                            <TripStatusCell :trip="trip" />
                        </td>

                        <td v-if="isVisible('overnight')" class="border px-2 py-2 align-middle dark:border-gray-700">
                            <div class="flex items-center justify-center">
                                <CircleCheckBigIcon v-if="trip.trip_expense?.is_overnight" class="h-4 w-4 text-green-500" />
                                <CircleX v-else class="h-4 w-4 text-gray-400" />
                            </div>
                        </td>

                        <td v-if="isVisible('holiday')" class="border px-2 py-2 align-middle dark:border-gray-700">
                            <div class="flex items-center justify-center">
                                <CircleCheckBigIcon v-if="trip.trip_expense?.is_holiday" class="h-4 w-4 text-green-500" />
                                <CircleX v-else class="h-4 w-4 text-gray-400" />
                            </div>
                        </td>

                        <td v-if="isVisible('totalFee')" class="whitespace-nowrap border px-2 py-2 text-right align-middle dark:border-gray-700">
                            {{ formatMoney(trip.total_fee) }}
                        </td>
                        <td v-if="isVisible('created_at')" class="whitespace-nowrap border px-2 py-2 text-center align-middle dark:border-gray-700">
                            {{ formatDateTime(trip.created_at) }}
                        </td>
                        <td v-if="isVisible('updated_at')" class="whitespace-nowrap border px-2 py-2 text-center align-middle dark:border-gray-700">
                            {{ formatDateTime(trip.updated_at) }}
                        </td>
                        <td v-if="isVisible('submitted_at')" class="whitespace-nowrap border px-2 py-2 text-center align-middle dark:border-gray-700">
                            {{ formatDateTime(trip.submitted_at) }}
                        </td>
                        <td v-if="isVisible('reviewed_at')" class="whitespace-nowrap border px-2 py-2 text-center align-middle dark:border-gray-700">
                            {{ formatDateTime(trip.reviewed_at) }}
                        </td>
                        
                        <td v-if="isVisible('reviewer')" class="whitespace-nowrap border px-2 py-2 text-center align-middle dark:border-gray-700">
                            {{ trip.reviewer?.name?? '-' }}
                        </td>

                        <td v-if="isVisible('actions')" 
                            class="sticky right-0 bg-inherit border px-2 py-2 align-middle dark:border-gray-700"
                            :class="[cellBorder, stickyRightCell, 'w-px min-w-max whitespace-nowrap']"
                        >
                            <div class="flex w-max min-w-full flex-nowrap items-center justify-center gap-2 whitespace-nowrap">
                                <div class="flex shrink-0 justify-center">
                                    <TripActions
                                        :trip="trip"
                                        variant="table"
                                        :only="tableActions"
                                        :confirming="confirmingId === trip.id"
                                        @action="emit('action', $event)"
                                    />
                                </div>
                                <template v-if="bulkReview">
                                    <div class="flex size-4 shrink-0 items-center justify-center">
                                        <Checkbox
                                            v-if="canBulkReview(trip)"
                                            :model-value="selectedTripIds.has(trip.id)"
                                            :aria-label="$t('trip.bulkReview.selectTrip', { id: trip.id })"
                                            @update:model-value="(checked) => setTripSelected(trip.id, checked)"
                                        />
                                    </div>
                                </template>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>