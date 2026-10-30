<script setup lang="ts">
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { useFormat } from '@/composables/useFormat';
import { useStickyShadow } from '@/composables/useStickyShadow';
import { useTableColumns } from '@/composables/useTableColumns';
import type { AppExpense, ExpenseActionType } from '@/types/expense';
import { ADMIN_EXPENSE_TABLE_ACTIONS } from '@/types/expense-table';
import { DEFAULT_EXPENSE_TABLE_COLUMNS, EXPENSE_TABLE_COLUMNS } from '@/types/expense-table';
import type { ExpenseColumnKey } from '@/types/expense-table';
import ExpenseActions from './ExpenseActions.vue';
import ExpenseColumnPicker from './ExpenseColumnPicker.vue';

const props = withDefaults(defineProps<{
    expenses: AppExpense[];
    startIndex?: number;
    processingId?: number | null;
    storageKey?: string;
    defaultColumns?: ExpenseColumnKey[];
    tableActions?: readonly ExpenseActionType[];
    selected?: number[];
    bulkProcessing?: boolean;
}>(), {
    startIndex: 1,
    processingId: null,
    storageKey: 'admin-expense-columns',
    defaultColumns: () => DEFAULT_EXPENSE_TABLE_COLUMNS,
    tableActions: () => ADMIN_EXPENSE_TABLE_ACTIONS,
    selected: () => [],
    bulkProcessing: false,
});

const emit = defineEmits<{
    (event: 'action', payload: { type: ExpenseActionType; expense: AppExpense }): void;
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
    columns: EXPENSE_TABLE_COLUMNS,
    storageKey: props.storageKey,
    defaultColumns: props.defaultColumns,
});

const scroller = ref<HTMLElement | null>(null);
const { atStart, atEnd } = useStickyShadow(scroller);
const stickyLeftBody = computed(() => [
    'sticky left-0 z-20 bg-inherit',
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
const stickyRightHead = computed(() => [
    'sticky right-0 top-0 z-40 bg-gray-100 dark:bg-gray-800',
    !atEnd.value ? 'shadow-[-6px_0_6px_-6px_rgba(0,0,0,0.25)]' : '',
]);
const cellBorder = 'border-b border-r border-gray-300 dark:border-gray-700';
const { formatDateTime, formatMoney } = useFormat();
const selectableIds = computed(() =>
    props.expenses
        .filter((expense) => expense.can?.delete || expense.can?.toggleActive)
        .map((expense) => expense.id),
);
const allSelected = computed(() =>
    selectableIds.value.length > 0 && selectableIds.value.every((id) => props.selected.includes(id)),
);
const someSelected = computed(() => props.selected.length > 0 && !allSelected.value);
const isActive = (expense: AppExpense) => expense.is_active === true || expense.is_active === 1;

const toggleRow = (id: number, checked: boolean) => {
    emit('update:selected', checked
        ? [...new Set([...props.selected, id])]
        : props.selected.filter((selectedId) => selectedId !== id));
};

const toggleAllRows = (checked: boolean) => {
    emit('update:selected', checked ? [...selectableIds.value] : []);
};
</script>

<template>
    <div class="space-y-3">
        <div class="flex justify-end">
            <ExpenseColumnPicker
                :optional-columns="optionalColumns"
                :visible-column-count="visibleColumnCount"
                :total-column-count="EXPENSE_TABLE_COLUMNS.length"
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
                                column.key === 'actions' ? 'w-24 min-w-24 max-w-24 !px-0' : '',
                                column.key === 'actions' ? stickyRightHead : '',
                            ]"
                        >
                            <Checkbox
                                v-if="column.key === 'select'"
                                :model-value="allSelected ? true : someSelected ? 'indeterminate' : false"
                                :disabled="!selectableIds.length || bulkProcessing"
                                :aria-label="$t('expense.actions.selectAll')"
                                @update:model-value="(value) => toggleAllRows(value === true)"
                            />
                            <template v-else>{{ $t(column.labelKey) }}</template>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!expenses.length">
                        <td :colspan="visibleColumnCount" class="px-4 py-8 text-center text-muted-foreground">
                            {{ $t('expense.filters.resultEmpty') }}
                        </td>
                    </tr>
                    <tr
                        v-for="(expense, index) in expenses"
                        :key="expense.id"
                        class="bg-white transition-colors hover:bg-gray-50 dark:bg-gray-950 dark:hover:bg-gray-900"
                        :class="selected.includes(expense.id) ? 'bg-primary/5 dark:bg-primary/10' : ''"
                    >
                        <td
                            v-if="isVisible('select')"
                            class="w-10 min-w-10 max-w-10 px-2 py-2 text-center align-middle"
                            :class="[cellBorder, stickyLeftBody]"
                        >
                            <Checkbox
                                :model-value="selected.includes(expense.id)"
                                :disabled="(!expense.can?.delete && !expense.can?.toggleActive) || bulkProcessing"
                                :aria-label="$t('expense.actions.selectRow', { name: expense.name })"
                                @update:model-value="(value) => toggleRow(expense.id, value === true)"
                            />
                        </td>
                        <td
                            v-if="isVisible('id')"
                            class="px-2 py-2 text-center align-middle text-sm font-medium"
                            :class="cellBorder"
                        >
                            <button
                                type="button"
                                class="inline-flex min-w-8 items-center justify-center rounded-md px-2 py-1 text-primary underline-offset-4 transition-colors hover:bg-primary/10 hover:underline focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1"
                                :title="$t('expense.actions.viewNumber', { id: expense.id })"
                                :aria-label="$t('expense.actions.viewNumber', { id: expense.id })"
                                @click="emit('action', { type: 'view', expense })"
                            >
                                {{ startIndex + index }}
                            </button>
                        </td>
                        <td v-if="isVisible('name')" class="max-w-56 truncate px-3 py-2 text-sm" :class="cellBorder">
                            {{ expense.name }}
                        </td>
                        <td v-if="isVisible('overkm_rate')" class="whitespace-nowrap px-3 py-2 text-right text-sm" :class="cellBorder">
                            {{ expense.overkm_rate === null ? '—' : formatMoney(expense.overkm_rate) }}
                        </td>
                        <td v-if="isVisible('overtime_rate')" class="whitespace-nowrap px-3 py-2 text-right text-sm" :class="cellBorder">
                            {{ expense.overtime_rate === null ? '—' : formatMoney(expense.overtime_rate) }}
                        </td>
                        <td v-if="isVisible('overnight_rate')" class="whitespace-nowrap px-3 py-2 text-right text-sm" :class="cellBorder">
                            {{ expense.overnight_rate === null ? '—' : formatMoney(expense.overnight_rate) }}
                        </td>
                        <td v-if="isVisible('holiday_rate')" class="whitespace-nowrap px-3 py-2 text-right text-sm" :class="cellBorder">
                            {{ expense.holiday_rate === null ? '—' : formatMoney(expense.holiday_rate) }}
                        </td>
                        <td v-if="isVisible('is_active')" class="px-3 py-2 text-center" :class="cellBorder">
                            <Badge :variant="isActive(expense) ? 'default' : 'secondary'">
                                {{ isActive(expense) ? $t('expense.status.active') : $t('expense.status.inactive') }}
                            </Badge>
                        </td>
                        <td v-if="isVisible('created_by')" class="max-w-48 truncate px-3 py-2 text-sm" :class="cellBorder">
                            {{ expense.creator?.name ?? '—' }}
                        </td>
                        <td v-if="isVisible('created_at')" class="whitespace-nowrap px-3 py-2 text-sm" :class="cellBorder">
                            {{ formatDateTime(expense.created_at) || '—' }}
                        </td>
                        <td v-if="isVisible('updated_at')" class="whitespace-nowrap px-3 py-2 text-sm" :class="cellBorder">
                            {{ formatDateTime(expense.updated_at) || '—' }}
                        </td>
                        <td v-if="isVisible('note')" class="max-w-[150px] truncate px-3 py-2 text-sm" :class="cellBorder" :title="expense.note??'' ">
                            {{ expense.note || '—' }}
                        </td>
                        <td v-if="isVisible('actions')" class="w-24 min-w-24 max-w-24 px-0 py-1" :class="[cellBorder, stickyRightBody]">
                            <ExpenseActions
                                :expense="expense"
                                :only="tableActions"
                                :processing="processingId === expense.id"
                                @action="emit('action', $event)"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
