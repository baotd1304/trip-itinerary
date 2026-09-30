<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { CheckCheck, RotateCcw, Settings2 } from 'lucide-vue-next';
import type { ColumnKey, TableColumn } from '@/types/trip-table';

defineProps<{
    optionalColumns: TableColumn[];
    visibleColumnCount: number;
    totalColumnCount: number;
    selectedOptionalCount: number;
    allSelected: boolean;
    isVisible: (key: ColumnKey) => boolean;
}>();

const emit = defineEmits<{
    (e: 'toggle', key: ColumnKey, checked: boolean): void;
    (e: 'toggle-all'): void;
    (e: 'reset'): void;
}>();

const onToggle = (key: ColumnKey, value: boolean | 'indeterminate') =>
    emit('toggle', key, value === true);
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button type="button" variant="outline" size="sm" class="h-8 gap-1.5 px-2.5">
                <Settings2 class="h-3.5 w-3.5" />
                <span class="hidden sm:inline">{{ $t('trip.table.chooseColumns') }}</span>
                <span
                    class="rounded bg-muted px-1.5 py-0.5 text-[11px] font-medium text-muted-foreground"
                >
                    {{ visibleColumnCount }}/{{ totalColumnCount }}
                </span>
            </Button>
        </PopoverTrigger>

        <PopoverContent align="end" :side-offset="6" class="w-64 p-2">
            <div class="mb-2 flex items-start justify-between gap-2 px-1">
                <div class="min-w-0">
                    <p class="text-sm font-semibold leading-5">
                        {{ $t('trip.table.displayColumns') }}
                    </p>
                    <p class="text-[11px] text-muted-foreground">
                        {{ $t('trip.table.columnsSelected', {
                            count: selectedOptionalCount,
                            total: optionalColumns.length,
                        }) }}
                    </p>
                </div>

                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-7 shrink-0 gap-1 px-2 text-xs"
                    @click="emit('toggle-all')"
                >
                    <CheckCheck class="h-3.5 w-3.5" />
                    {{ allSelected ? $t('common.clearAll') : $t('common.checkAll') }}
                </Button>
            </div>

            <div class="mb-2 border-t" />

            <div class="max-h-72 space-y-0.5 overflow-y-auto pr-1">
                <label
                    v-for="column in optionalColumns"
                    :key="column.key"
                    class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm transition-colors hover:bg-muted"
                >
                    <Checkbox
                        :model-value="isVisible(column.key)"
                        :aria-label="$t(column.labelKey)"
                        @update:model-value="(v) => onToggle(column.key, v)"
                    />
                    <span class="min-w-0 flex-1 truncate">{{ $t(column.labelKey) }}</span>
                </label>
            </div>

            <div class="mt-2 border-t pt-2">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-8 w-full justify-start gap-2 text-xs"
                    @click="emit('reset')"
                >
                    <RotateCcw class="h-3.5 w-3.5" />
                    {{ $t('trip.table.resetColumns') }}
                </Button>
            </div>
        </PopoverContent>
    </Popover>
</template>