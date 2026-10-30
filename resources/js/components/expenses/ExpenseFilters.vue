<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ExpenseCreator } from '@/types/expense';

interface ExpenseFiltersState {
    search?: string | null;
    is_active?: '1' | '0' | null;
    created_by?: number | null;
}

const props = withDefaults(defineProps<{
    url: string;
    creators: ExpenseCreator[];
    initial?: ExpenseFiltersState;
    only?: string[];
}>(), {
    only: () => ['expenses', 'expensesMeta', 'stats', 'creators', 'filters'],
});

const state = reactive({
    search: '',
    is_active: '',
    created_by: '',
});

const activeFilterCount = computed(() =>
    (state.search.trim() ? 1 : 0)
    + (state.is_active ? 1 : 0)
    + (state.created_by ? 1 : 0),
);

watch(
    () => props.initial,
    (filters) => {
        state.search = filters?.search ?? '';
        state.is_active = filters?.is_active ?? '';
        state.created_by = filters?.created_by ? String(filters.created_by) : '';
    },
    { immediate: true, deep: true },
);

const apply = () => {
    const filters = Object.fromEntries(
        Object.entries(state).filter(([, value]) => value !== ''),
    );

    router.get(props.url, filters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: props.only,
    });
};

const reset = () => {
    Object.assign(state, {
        search: '',
        is_active: '',
        created_by: '',
    });
    apply();
};

const applyStatsFilter = (status: 'active' | 'inactive') => {
    state.is_active = status === 'active' ? '1' : '0';
    apply();
};

defineExpose({ applyStatsFilter });
</script>

<template>
    <form class="mb-4 grid gap-2 rounded-lg border p-3 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="apply">
        <div class="grid gap-1.5">
            <Label for="expense-filter-search" class="text-xs text-muted-foreground">{{ $t('common.search') }}</Label>
            <Input id="expense-filter-search" v-model="state.search" :placeholder="$t('expense.filters.searchPlaceholder')" />
        </div>
        <div class="grid gap-1.5">
            <Label for="expense-filter-status" class="text-xs text-muted-foreground">{{ $t('expense.filters.status') }}</Label>
            <select id="expense-filter-status" v-model="state.is_active" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                <option value="">{{ $t('expense.filters.allStatuses') }}</option>
                <option value="1">{{ $t('expense.status.active') }}</option>
                <option value="0">{{ $t('expense.status.inactive') }}</option>
            </select>
        </div>
        <div class="grid gap-1.5">
            <Label for="expense-filter-creator" class="text-xs text-muted-foreground">{{ $t('expense.filters.creator') }}</Label>
            <select id="expense-filter-creator" v-model="state.created_by" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                <option value="">{{ $t('expense.filters.allCreators') }}</option>
                <option v-for="creator in creators" :key="creator.id" :value="String(creator.id)">
                    {{ creator.name }}
                </option>
            </select>
        </div>
        <div class="flex justify-end gap-2 sm:col-span-2 lg:col-span-4">
            <Button type="button" variant="outline" size="sm" @click="reset">
                {{ $t('expense.filters.reset') }}
            </Button>
            <Button type="submit" size="sm" class="relative">
                {{ $t('common.search') }}
                <Badge
                    v-if="activeFilterCount"
                    variant="destructive"
                    class="absolute -top-2 -right-2 flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[11px]"
                    :aria-label="$t('expense.filters.activeCount', { count: activeFilterCount })"
                    :title="$t('expense.filters.activeCount', { count: activeFilterCount })"
                >
                    {{ activeFilterCount }}
                </Badge>
            </Button>
        </div>
    </form>
</template>
