<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { RoleRef, UserRef } from '@/types/user';

interface UserFiltersState {
    search?: string | null;
    role?: string | null;
    is_active?: '1' | '0' | null;
    verified?: '1' | '0' | null;
    created_by?: number | null;
    from?: string | null;
    to?: string | null;
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
    from: '',
    to: '',
});

watch(
    () => props.initial,
    (filters) => {
        state.search = filters?.search ?? '';
        state.role = filters?.role ?? '';
        state.is_active = filters?.is_active ?? '';
        state.verified = filters?.verified ?? '';
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
        role: '',
        is_active: '',
        verified: '',
        created_by: '',
        from: '',
        to: '',
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
        <Input v-model="state.search" :placeholder="$t('user.filters.searchPlaceholder')" />
        <select v-model="state.role" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
            <option value="">{{ $t('user.filters.allRoles') }}</option>
            <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
        </select>
        <select v-model="state.is_active" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
            <option value="">{{ $t('user.filters.allStatuses') }}</option>
            <option value="1">{{ $t('user.status.active') }}</option>
            <option value="0">{{ $t('user.status.inactive') }}</option>
        </select>
        <select v-model="state.verified" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
            <option value="">{{ $t('user.filters.allVerification') }}</option>
            <option value="1">{{ $t('user.status.verified') }}</option>
            <option value="0">{{ $t('user.status.unverified') }}</option>
        </select>
        <select v-model="state.created_by" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
            <option value="">{{ $t('user.filters.allCreators') }}</option>
            <option v-for="creator in creators" :key="creator.id" :value="String(creator.id)">
                {{ creator.name }}
            </option>
        </select>
        <Input v-model="state.from" type="date" :aria-label="$t('user.filters.from')" />
        <Input v-model="state.to" type="date" :aria-label="$t('user.filters.to')" />
        <div class="flex justify-end gap-2 sm:col-span-2 lg:col-span-4">
            <Button type="button" variant="outline" size="sm" @click="reset">{{ $t('user.filters.reset') }}</Button>
            <Button type="submit" size="sm">{{ $t('common.search') }}</Button>
        </div>
    </form>
</template>
