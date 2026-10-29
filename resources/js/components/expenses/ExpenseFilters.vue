<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { ExpenseCreator } from '@/types/expense';

interface ExpenseFiltersState {
    search?: string | null;
    is_active?: '1' | '0' | null;
    created_by?: number | null;
    from?: string | null;
    to?: string | null;
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
    from: '',
    to: '',
});

const activeFilterCount = computed(() =>
    (state.search.trim() ? 1 : 0)
    + (state.is_active ? 1 : 0)
    + (state.created_by ? 1 : 0)
    + (state.from ? 1 : 0)
    + (state.to ? 1 : 0),
);

watch(
    () => props.initial,
    (filters) => {
        state.search = filters?.search ?? '';
        state.is_active = filters?.is_active ?? '';
        state.created_by = filters?.created_by ? String(filters.created_by) : '';
        state.from = filters?.from ?? '';
        state.to = filters?.to ?? '';
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
        from: '',
        to: '',
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
        <Input v-model="state.search" :placeholder="$t('expense.filters.searchPlaceholder')" />
        <select
            v-model="state.is_active"
            class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
            :aria-label="$t('expense.filters.allStatuses')"
        >
            <option value="">{{ $t('expense.filters.allStatuses') }}</option>
            <option value="1">{{ $t('expense.status.active') }}</option>
            <option value="0">{{ $t('expense.status.inactive') }}</option>
        </select>
        <select
            v-model="state.created_by"
            class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
            :aria-label="$t('expense.filters.allCreators')"
        >
            <option value="">{{ $t('expense.filters.allCreators') }}</option>
            <option v-for="creator in creators" :key="creator.id" :value="String(creator.id)">
                {{ creator.name }}
            </option>
        </select>
        <Input v-model="state.from" type="date" :aria-label="$t('expense.filters.from')" />
        <Input v-model="state.to" type="date" :aria-label="$t('expense.filters.to')" />
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
