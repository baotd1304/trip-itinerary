<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface CarFiltersState {
    search?: string | null;
    brand?: string | null;
    is_active?: '1' | '0' | null;
    from?: string | null;
    to?: string | null;
}

const props = withDefaults(defineProps<{
    url: string;
    brands: string[];
    initial?: CarFiltersState;
    only?: string[];
}>(), {
    only: () => ['cars', 'carsMeta', 'stats', 'brands', 'filters'],
});

const state = reactive({
    search: '',
    brand: '',
    is_active: '',
    from: '',
    to: '',
});

const activeFilterCount = computed(() =>
    (state.search.trim() ? 1 : 0)
    + (state.brand ? 1 : 0)
    + (state.is_active ? 1 : 0)
    + (state.from ? 1 : 0)
    + (state.to ? 1 : 0),
);

watch(
    () => props.initial,
    (filters) => {
        state.search = filters?.search ?? '';
        state.brand = filters?.brand ?? '';
        state.is_active = filters?.is_active ?? '';
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
        brand: '',
        is_active: '',
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
        <Input v-model="state.search" :placeholder="$t('car.filters.searchPlaceholder')" />
        <select
            v-model="state.brand"
            class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
            :aria-label="$t('car.filters.allBrands')"
        >
            <option value="">{{ $t('car.filters.allBrands') }}</option>
            <option v-for="brand in brands" :key="brand" :value="brand">{{ brand }}</option>
        </select>
        <select
            v-model="state.is_active"
            class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
            :aria-label="$t('car.filters.allStatuses')"
        >
            <option value="">{{ $t('car.filters.allStatuses') }}</option>
            <option value="1">{{ $t('car.status.active') }}</option>
            <option value="0">{{ $t('car.status.inactive') }}</option>
        </select>
        <Input v-model="state.from" type="date" :aria-label="$t('car.filters.from')" />
        <Input v-model="state.to" type="date" :aria-label="$t('car.filters.to')" />
        <div class="flex justify-end gap-2 sm:col-span-2 lg:col-span-4">
            <Button type="button" variant="outline" size="sm" @click="reset">
                {{ $t('car.filters.reset') }}
            </Button>
            <Button type="submit" size="sm" class="relative">
                {{ $t('common.search') }}
                <Badge
                    v-if="activeFilterCount"
                    variant="destructive"
                    class="absolute -top-2 -right-2 flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[11px]"
                    :aria-label="$t('car.filters.activeCount', { count: activeFilterCount })"
                    :title="$t('car.filters.activeCount', { count: activeFilterCount })"
                >
                    {{ activeFilterCount }}
                </Badge>
            </Button>
        </div>
    </form>
</template>
