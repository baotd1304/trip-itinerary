<script setup lang="ts">
import { computed, ref } from 'vue';
import { CircleCheckBigIcon, CircleX } from 'lucide-vue-next';
import TripActions from './TripActions.vue';
import TripStatusCell from './TripStatusCell.vue';
import TripColumnPicker from './TripColumnPicker.vue';
import { useFormat } from '@/composables/useFormat';
import { useTableColumns } from '@/composables/useTableColumns';
import { useStickyShadow } from '@/composables/useStickyShadow';
import { TABLE_ONLY_VIEW } from '@/lib/trip-action-presets';
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
  }>(),
  {
    confirmingId: null,
    startIndex: 1,
    storageKey: 'trip-table-visible-columns',
    defaultColumns: () => DEFAULT_TRIP_TABLE_COLUMNS,
    tableActions: () => TABLE_ONLY_VIEW,
  },
);

const emit = defineEmits<{
    (e: 'action', payload: { type: TripActionType; trip: Trip }): void;
}>();

const { formatDate, formatMoney } = useFormat();

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

/** Chỉ ghim khi cột đó thực sự đang hiển thị */
const pinLeft = computed(() => isVisible('stt'));
const pinRight = computed(() => isVisible('actions'));

/* ---- Lớp dùng lại cho ô ghim ---- */
const stickyLeftCell = computed(() => [
    'sticky left-0 z-10 bg-inherit',
    !atStart.value ? 'shadow-[6px_0_6px_-6px_rgba(0,0,0,0.25)]' : '',
]);
const stickyRightCell = computed(() => [
    'sticky right-0 z-10 bg-inherit',
    !atEnd.value ? 'shadow-[-6px_0_6px_-6px_rgba(0,0,0,0.25)]' : '',
]);
const stickyLeftHead = computed(() => [
    'sticky left-0 z-30 bg-inherit',
    !atStart.value ? 'shadow-[6px_0_6px_-6px_rgba(0,0,0,0.25)]' : '',
]);
const stickyRightHead = computed(() => [
    'sticky right-0 z-30 bg-inherit',
    !atEnd.value ? 'shadow-[-6px_0_6px_-6px_rgba(0,0,0,0.25)]' : '',
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
        <div class="flex justify-end">
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
        <!-- Scroller -->
        <div
            ref="scroller"
            class="overflow-x-auto rounded-lg border border-gray-300 dark:border-gray-700"
        >
            <div class="overflow-x-auto rounded-lg border border-gray-300 dark:border-gray-700">
                <table class="w-full table-auto border-separate border-spacing-0">
                    <thead>
                        <tr class="bg-gray-100 dark:bg-gray-800">
                            <th
                                v-for="column in renderedColumns"
                                :key="column.key" scope="col"
                                class="whitespace-nowrap border border-gray-300 px-2 py-2 text-center dark:border-gray-700"
                                :class="[
                                        cellBorder,
                                        column.key === 'stt' && pinLeft ? stickyLeftHead : '',
                                        column.key === 'actions' && pinRight ? stickyRightHead : '',
                                    ]"
                            >
                                {{ $t(column.labelKey) }}
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
                            <td v-if="isVisible('advisor')" class="border px-2 py-2 text-center align-middle dark:border-gray-700">
                                {{ trip.advisor?.name ?? '—' }}
                            </td>
                            <td v-if="isVisible('driver')" class="border px-2 py-2 text-center align-middle dark:border-gray-700">
                                {{ trip.driver?.name ?? '—' }}
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
                            <td v-if="isVisible('day')" class="whitespace-nowrap border px-2 py-2 text-center align-middle dark:border-gray-700">
                                {{ formatDate(trip.day) }}
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
                            
                            <td v-if="isVisible('reviewer')" class="whitespace-nowrap border px-2 py-2 text-right align-middle dark:border-gray-700">
                                {{ trip.reviewer?.name?? '-' }}
                            </td>

                            <td v-if="isVisible('actions')" 
                                class="sticky right-0 bg-inherit border px-2 py-2 align-middle dark:border-gray-700"
                                :class="[cellBorder, stickyRightCell]"
                            >
                                <TripActions
                                    :trip="trip"
                                    variant="table"
                                    :only="tableActions"
                                    :confirming="confirmingId === trip.id"
                                    @action="emit('action', $event)"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>