<script setup lang="ts">
import { computed, ref } from 'vue';
import { Checkbox } from '@/components/ui/checkbox';
import { useFormat } from '@/composables/useFormat';
import { useStickyShadow } from '@/composables/useStickyShadow';
import { useTableColumns } from '@/composables/useTableColumns';
import { ADMIN_CAR_TABLE_ACTIONS } from '@/lib/car-action-presets';
import type { AppCar, CarActionType } from '@/types/car';
import { CAR_TABLE_COLUMNS, DEFAULT_CAR_TABLE_COLUMNS } from '@/types/car-table';
import type { CarColumnKey } from '@/types/car-table';
import CarActions from './CarActions.vue';
import CarColumnPicker from './CarColumnPicker.vue';

const props = withDefaults(defineProps<{
    cars: AppCar[];
    processingId?: number | null;
    storageKey?: string;
    defaultColumns?: CarColumnKey[];
    tableActions?: CarActionType[];
    selected?: number[];
    bulkProcessing?: boolean;
}>(), {
    processingId: null,
    storageKey: 'admin-car-columns',
    defaultColumns: () => DEFAULT_CAR_TABLE_COLUMNS,
    tableActions: () => ADMIN_CAR_TABLE_ACTIONS,
    selected: () => [],
    bulkProcessing: false,
});

const emit = defineEmits<{
    (event: 'action', payload: { type: CarActionType; car: AppCar }): void;
    (event: 'update:selected', ids: number[]): void;
}>();

const {
    renderedColumns,
    optionalColumns,
    visibleColumnCount,
    selectedOptionalCount,
    allOptionalSelected,
    isVisible,
    toggle,
    toggleAll,
    resetToDefault,
} = useTableColumns({
    columns: CAR_TABLE_COLUMNS,
    storageKey: props.storageKey,
    defaultColumns: props.defaultColumns,
});

const scroller = ref<HTMLElement | null>(null);
const { atStart, atEnd } = useStickyShadow(scroller);
const stickyLeftBody = computed(() => [
    'sticky left-0 z-20 bg-inherit',
    !atStart.value ? 'shadow-[6px_0_6px_-6px_rgba(0,0,0,0.25)]' : '',
]);
const stickySecondLeftBody = computed(() => [
    'sticky z-20 bg-inherit',
    isVisible('select') ? 'left-10' : 'left-0',
    !atStart.value ? 'shadow-[6px_0_6px_-6px_rgba(0,0,0,0.25)]' : '',
]);
const stickyRightBody = computed(() => [
    'sticky right-0 z-20 bg-inherit',
    !atEnd.value ? 'shadow-[-6px_0_6px_-6px_rgba(0,0,0,0.25)]' : '',
]);
const stickyLeftHead = computed(() => [
    'sticky left-0 top-0 z-40 bg-gray-100 dark:bg-gray-800',
    !atStart.value ? 'shadow-[6px_0_6px_-6px_rgba(0,0,0,0.25)]' : '',
]);
const stickySecondLeftHead = computed(() => [
    'sticky top-0 z-40 bg-gray-100 dark:bg-gray-800',
    isVisible('select') ? 'left-10' : 'left-0',
    !atStart.value ? 'shadow-[6px_0_6px_-6px_rgba(0,0,0,0.25)]' : '',
]);
const stickyRightHead = computed(() => [
    'sticky right-0 top-0 z-40 bg-gray-100 dark:bg-gray-800',
    !atEnd.value ? 'shadow-[-6px_0_6px_-6px_rgba(0,0,0,0.25)]' : '',
]);
const cellBorder = 'border-b border-r border-gray-300 dark:border-gray-700';
const { formatDateTime } = useFormat();
const selectableIds = computed(() =>
    props.cars.filter((car) => car.can?.delete || car.can?.toggleActive).map((car) => car.id),
);
const allSelected = computed(() =>
    selectableIds.value.length > 0 && selectableIds.value.every((id) => props.selected.includes(id)),
);
const someSelected = computed(() => props.selected.length > 0 && !allSelected.value);
const isActive = (car: AppCar) => car.is_active === true || car.is_active === 1;

const toggleRow = (id: number, checked: boolean) => {
    const next = checked
        ? [...new Set([...props.selected, id])]
        : props.selected.filter((selectedId) => selectedId !== id);

    emit('update:selected', next);
};

const toggleAllRows = (checked: boolean) => {
    emit('update:selected', checked ? [...selectableIds.value] : []);
};
</script>

<template>
    <div class="space-y-3">
        <div class="flex justify-end">
            <CarColumnPicker
                :optional-columns="optionalColumns"
                :visible-column-count="visibleColumnCount"
                :total-column-count="CAR_TABLE_COLUMNS.length"
                :selected-optional-count="selectedOptionalCount"
                :all-selected="allOptionalSelected"
                :is-visible="isVisible"
                @toggle="toggle"
                @toggle-all="toggleAll"
                @reset="resetToDefault"
            />
        </div>

        <div ref="scroller" class="relative max-h-[70vh] max-w-full overflow-auto rounded-lg border border-gray-300 dark:border-gray-700">
            <table class="w-full table-auto border-separate border-spacing-0">
                <thead class="sticky top-0 z-30">
                    <tr class="bg-gray-100 dark:bg-gray-800">
                        <th
                            v-for="column in renderedColumns"
                            :key="column.key"
                            scope="col"
                            class="whitespace-nowrap border-b border-r border-gray-300 bg-gray-100 px-2 py-2 text-center align-middle text-sm font-semibold dark:border-gray-700 dark:bg-gray-800"
                            :class="[
                                cellBorder,
                                column.key === 'select' ? 'w-10 min-w-10 max-w-10' : '',
                                column.key === 'select' ? stickyLeftHead : '',
                                column.key === 'id' ? stickySecondLeftHead : '',
                                column.key === 'actions' ? stickyRightHead : '',
                            ]"
                        >
                            <Checkbox
                                v-if="column.key === 'select'"
                                :model-value="allSelected"
                                :indeterminate="someSelected"
                                :disabled="!selectableIds.length || bulkProcessing"
                                :aria-label="$t('car.actions.selectAll')"
                                @update:model-value="(value) => toggleAllRows(value === true)"
                            />
                            <template v-else>{{ $t(column.labelKey) }}</template>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!cars.length">
                        <td :colspan="visibleColumnCount" class="px-4 py-8 text-center text-muted-foreground">
                            {{ $t('common.noData') }}
                        </td>
                    </tr>
                    <tr
                        v-for="car in cars"
                        :key="car.id"
                        class="bg-white transition-colors hover:bg-gray-50 dark:bg-gray-950 dark:hover:bg-gray-900"
                        :class="selected.includes(car.id) ? 'bg-primary/5 dark:bg-primary/10' : ''"
                    >
                        <td
                            v-if="isVisible('select')"
                            class="w-10 min-w-10 max-w-10 px-2 py-2 text-center align-middle"
                            :class="[cellBorder, stickyLeftBody]"
                        >
                            <Checkbox
                                :model-value="selected.includes(car.id)"
                                :disabled="(!car.can?.delete && !car.can?.toggleActive) || bulkProcessing"
                                :aria-label="$t('car.actions.selectRow', { name: car.name ?? car.license_plate })"
                                @update:model-value="(value) => toggleRow(car.id, value === true)"
                            />
                        </td>
                        <td
                            v-if="isVisible('id')"
                            class="px-2 py-2 text-center align-middle text-sm font-medium"
                            :class="[cellBorder, stickySecondLeftBody]"
                        >
                            <button
                                type="button"
                                class="inline-flex min-w-8 items-center justify-center rounded-md px-2 py-1 text-primary underline-offset-4 transition-colors hover:bg-primary/10 hover:underline focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1"
                                :title="$t('car.actions.viewNumber', { id: car.id })"
                                :aria-label="$t('car.actions.viewNumber', { id: car.id })"
                                @click="emit('action', { type: 'view', car })"
                            >
                                {{ car.id }}
                            </button>
                        </td>
                        <td v-if="isVisible('name')" class="max-w-[220px] truncate px-2 py-2 text-left align-middle font-medium" :class="cellBorder" :title="car.name ?? ''">
                            {{ car.name ?? '—' }}
                        </td>
                        <td v-if="isVisible('brand')" class="max-w-[150px] truncate whitespace-nowrap px-2 py-2 text-center align-middle" :class="cellBorder" :title="car.brand ??''">
                            {{ car.brand ?? '—' }}
                        </td>
                        <td v-if="isVisible('model')" class="max-w-[150px] truncate whitespace-nowrap px-2 py-2 text-center align-middle" :class="cellBorder" :title="car.model ??''">
                            {{ car.model ?? '—' }}
                        </td>
                        <td v-if="isVisible('year')" class="whitespace-nowrap px-2 py-2 text-center align-middle" :class="cellBorder">
                            {{ car.year ?? '—' }}
                        </td>
                        <td v-if="isVisible('license_plate')" class="whitespace-nowrap px-2 py-2 text-center align-middle font-medium" :class="cellBorder">
                            {{ car.license_plate }}
                        </td>
                        <td v-if="isVisible('owner')" class="max-w-[200px] truncate px-2 py-2 text-center align-middle" :class="cellBorder" :title="car.owner ?? ''">
                            {{ car.owner ?? '—' }}
                        </td>
                        <td v-if="isVisible('is_active')" class="px-2 py-2 align-middle" :class="cellBorder">
                            <div class="flex items-center justify-center">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="isActive(car) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'"
                                >
                                    {{ isActive(car) ? $t('car.status.active') : $t('car.status.inactive') }}
                                </span>
                            </div>
                        </td>
                        <td v-if="isVisible('created_at')" class="whitespace-nowrap px-2 py-2 text-center align-middle" :class="cellBorder">
                            {{ formatDateTime(car.created_at) }}
                        </td>
                        <td v-if="isVisible('updated_at')" class="whitespace-nowrap px-2 py-2 text-center align-middle" :class="cellBorder">
                            {{ formatDateTime(car.updated_at) }}
                        </td>
                        <td v-if="isVisible('actions')" class="px-2 py-2 align-middle" :class="[cellBorder, stickyRightBody]">
                            <CarActions
                                :car="car"
                                :only="tableActions"
                                :processing="processingId === car.id"
                                @action="emit('action', $event)"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
