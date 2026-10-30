<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { RoleRef, UserRef } from '@/types/user';

interface UserFiltersState {
    search?: string | null;
    role?: string | null;
    is_active?: '1' | '0' | null;
    verified?: '1' | '0' | null;
    created_by?: number | null;
    sort?: string | null;
    dir?: 'asc' | 'desc' | null;
}

const props = withDefaults(defineProps<{
    url: string;
    initial?: UserFiltersState;
    roles: RoleRef[];
    creators: UserRef[];
    only?: string[];
}>(), {
    only: () => ['users', 'usersMeta', 'stats', 'filters'],
});

const state = reactive({
    search: '',
    role: '',
    is_active: '',
    verified: '',
    created_by: '',
});

const activeFilterCount = computed(
    () =>
        (state.search.trim() ? 1 : 0) +
        (state.role ? 1 : 0) +
        (state.is_active ? 1 : 0) +
        (state.verified ? 1 : 0) +
        (state.created_by ? 1 : 0),
);

watch(
    () => props.initial,
    (filters) => {
        state.search = filters?.search ?? '';
        state.role = filters?.role ?? '';
        state.is_active = filters?.is_active ?? '';
        state.verified = filters?.verified ?? '';
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
        role: '',
        is_active: '',
        verified: '',
        created_by: '',
    });
    apply();
};

const applyStatsFilter = (filter: 'active' | 'inactive' | 'unverified') => {
    state.is_active = filter === 'active' ? '1' : filter === 'inactive' ? '0' : '';
    state.verified = filter === 'unverified' ? '0' : '';
    apply();
};

defineExpose({ applyStatsFilter });
</script>

<template>
    <form class="mb-4 grid gap-2 rounded-lg border p-3 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="apply">
        <div class="grid gap-1.5">
            <Label for="user-filter-search" class="text-xs text-muted-foreground">{{ $t('common.search') }}</Label>
            <Input id="user-filter-search" v-model="state.search" :placeholder="$t('user.filters.searchPlaceholder')" />
        </div>
        <div class="grid gap-1.5">
            <Label for="user-filter-role" class="text-xs text-muted-foreground">{{ $t('user.filters.role') }}</Label>
            <select id="user-filter-role" v-model="state.role" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                <option value="">{{ $t('user.filters.allRoles') }}</option>
                <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
            </select>
        </div>
        <div class="grid gap-1.5">
            <Label for="user-filter-status" class="text-xs text-muted-foreground">{{ $t('user.filters.status') }}</Label>
            <select id="user-filter-status" v-model="state.is_active" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                <option value="">{{ $t('user.filters.allStatuses') }}</option>
                <option value="1">{{ $t('user.status.active') }}</option>
                <option value="0">{{ $t('user.status.inactive') }}</option>
            </select>
        </div>
        <div class="grid gap-1.5">
            <Label for="user-filter-verification" class="text-xs text-muted-foreground">{{ $t('user.filters.verification') }}</Label>
            <select id="user-filter-verification" v-model="state.verified" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                <option value="">{{ $t('user.filters.allVerification') }}</option>
                <option value="1">{{ $t('user.status.verified') }}</option>
                <option value="0">{{ $t('user.status.unverified') }}</option>
            </select>
        </div>
        <div class="grid gap-1.5">
            <Label for="user-filter-creator" class="text-xs text-muted-foreground">{{ $t('user.filters.creator') }}</Label>
            <select id="user-filter-creator" v-model="state.created_by" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                <option value="">{{ $t('user.filters.allCreators') }}</option>
                <option v-for="creator in creators" :key="creator.id" :value="String(creator.id)">
                    {{ creator.name }}
                </option>
            </select>
        </div>
        <div class="flex justify-end gap-2 sm:col-span-2 lg:col-span-4">
            <Button type="button" variant="outline" size="sm" @click="reset">{{ $t('user.filters.reset') }}</Button>
            <Button type="submit" size="sm" class="relative">
                {{ $t('common.search') }}
                <Badge
                    v-if="activeFilterCount"
                    variant="destructive"
                    class="absolute -top-2 -right-2 flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[11px]"
                    :aria-label="$t('user.filters.activeCount', { count: activeFilterCount })"
                    :title="$t('user.filters.activeCount', { count: activeFilterCount })"
                >
                    {{ activeFilterCount }}
                </Badge>
            </Button>
        </div>
    </form>
</template>
