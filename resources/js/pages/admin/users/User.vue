<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

import TripPagination from '@/components/trips/TripPagination.vue'; // tái dùng, không phụ thuộc domain

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import UserAccessControlDialog from '@/components/users/UserAccessControlDialog.vue';
import UserBulkDialog from '@/components/users/UserBulkDialog.vue';
import UserDeleteDialog from '@/components/users/UserDeleteDialog.vue';
import UserFilters from '@/components/users/UserFilters.vue';
import UserFormDialog from '@/components/users/UserFormDialog.vue';
import UserTable from '@/components/users/UserTable.vue';

import { usePagination } from '@/composables/usePagination';
import type { PaginationMeta } from '@/composables/usePagination';
import { useUserDialogs } from '@/composables/useUserDialogs';
import { toArray } from '@/lib/array';
import { ADMIN_USER_TABLE_ACTIONS } from '@/lib/user-action-presets';
import { adminUserRoutes } from '@/lib/user-routes-admin';
import type { PaginationLink } from '@/types/trip';
import type { AppUser, PermissionRef, RoleRef, UserRef } from '@/types/user';
import { ADMIN_DEFAULT_USER_TABLE_COLUMNS } from '@/types/user-table';

const { t } = useI18n();

defineOptions({ layout: undefined }); // layout mặc định khai báo ở app.ts

const props = defineProps<{
    users: { data?: AppUser[]; links?: PaginationLink[]; meta?: { links?: PaginationLink[] } } | AppUser[];
    usersMeta?: PaginationMeta;
    roles: { data?: RoleRef[] } | RoleRef[];
    permissions?: PermissionRef[];
    creators?: { data?: UserRef[] } | UserRef[];
    stats?: {
        total?: number;
        active?: number;
        inactive?: number;
        unverified?: number;
    };
    filters?: {
        search?: string | null;
        role?: string | null;
        is_active?: '1' | '0' | null;
        verified?: '1' | '0' | null;
        created_by?: number | null;
        from?: string | null;
        to?: string | null;
        sort?: string | null;
        dir?: 'asc' | 'desc' | null;
    };
    can?: { create?: boolean; manageAccessControl?: boolean };
}>();

/* ---------- Ref tới filter để badge thống kê bấm được ---------- */
const userFilters = ref<InstanceType<typeof UserFilters> | null>(null);

/* ---------- Chuẩn hoá dữ liệu ---------- */
const items = computed(() => toArray<AppUser>(props.users));
const roles = computed(() => toArray<RoleRef>(props.roles));
const creators = computed(() => toArray<UserRef>(props.creators ?? []));

const links = computed<PaginationLink[]>(() =>
    Array.isArray(props.users) ? [] : props.users.links ?? props.users.meta?.links ?? []);


/* ---------- Phân trang / STT ---------- */
const { total: totalCount, rangeFrom, rangeTo, startIndex } = usePagination(
    computed(() => props.users),
    computed(() => props.usersMeta),
    computed(() => items.value.length),
);
const canAssignRole = computed(() => props.can?.manageAccessControl === true);

/* ---------- Dialog ---------- */
const {
    mainOpen, mode, activeUser,
    deleteOpen, target,
    processingId,
    openCreate, handleAction,
} = useUserDialogs();

/* ---------- Thống kê ---------- */
const hasStats = computed(
    () => !!props.stats && Object.values(props.stats).some((v) => (v ?? 0) > 0),
);

/* ---------- Thao tác hàng loạt ---------- */
const selected = ref<number[]>([]);
const bulkOpen = ref(false);
const bulkAction = ref<'activate' | 'deactivate' | 'delete'>('activate');
const bulkProcessing = ref(false);
const accessControlOpen = ref(false);

const openBulk = (action: 'activate' | 'deactivate' | 'delete') => {
    if (!selected.value.length) {
return;
}

    bulkAction.value = action;
    bulkOpen.value = true;
};

const runBulk = () => {
    if (!selected.value.length || bulkProcessing.value) {
return;
}

    bulkProcessing.value = true;

    router.post(
        adminUserRoutes.bulk().url,
        { action: bulkAction.value, user_ids: selected.value },
        {
            preserveScroll: true,
            only: ['users', 'usersMeta', 'stats'],
            onSuccess: () => {
                selected.value = [];
                bulkOpen.value = false;
            },
            onFinish: () => {
                bulkProcessing.value = false;
            },
        },
    );
};
</script>

<template>
    <Head :title="t('user.admin.pageTitle')" />

    <Card class="overflow-hidden">
        <CardHeader>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-3">
                    <CardTitle>{{ $t('user.admin.listTitle') }}</CardTitle>

                    <div v-if="hasStats" class="flex flex-wrap items-center gap-1.5">
                        <Badge variant="secondary">
                            {{ $t('user.stats.total') }}: {{ props.stats?.total }}
                        </Badge>

                        <Badge
                            v-if="props.stats?.active"
                            as="button"
                            type="button"
                            :aria-pressed="props.filters?.is_active === '1'"
                            class="cursor-pointer bg-emerald-600 text-white focus-visible:outline-2 focus-visible:outline-offset-2"
                            @click="userFilters?.applyStatsFilter('active')"
                        >
                            {{ $t('user.status.active') }}: {{ props.stats.active }}
                        </Badge>

                        <Badge
                            v-if="props.stats?.inactive"
                            as="button"
                            type="button"
                            :aria-pressed="props.filters?.is_active === '0'"
                            class="cursor-pointer bg-gray-500 text-white focus-visible:outline-2 focus-visible:outline-offset-2"
                            @click="userFilters?.applyStatsFilter('inactive')"
                        >
                            {{ $t('user.status.inactive') }}: {{ props.stats.inactive }}
                        </Badge>

                        <Badge
                            v-if="props.stats?.unverified"
                            as="button"
                            type="button"
                            variant="outline"
                            :aria-pressed="props.filters?.verified === '0'"
                            class="cursor-pointer border-amber-500 text-amber-700 focus-visible:outline-2 focus-visible:outline-offset-2 dark:text-amber-400"
                            @click="userFilters?.applyStatsFilter('unverified')"
                        >
                            {{ $t('user.status.unverified') }}: {{ props.stats.unverified }}
                        </Badge>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        v-if="props.can?.manageAccessControl"
                        type="button"
                        variant="outline"
                        @click="accessControlOpen = true"
                    >
                        {{ $t('user.accessControl.manage') }}
                    </Button>
                    <!-- Thanh thao tác hàng loạt -->
                    <template v-if="selected.length">
                        <span class="text-xs text-muted-foreground">
                            {{ $t('common.selectedCount', { count: selected.length }) }}
                        </span>
                        <Button size="sm" variant="outline" :disabled="bulkProcessing" @click="openBulk('activate')">
                            {{ $t('user.actions.activate') }}
                        </Button>
                        <Button size="sm" variant="outline" :disabled="bulkProcessing" @click="openBulk('deactivate')">
                            {{ $t('user.actions.deactivate') }}
                        </Button>
                        <Button size="sm" variant="destructive" :disabled="bulkProcessing" @click="openBulk('delete')">
                            {{ $t('common.delete') }}
                        </Button>
                    </template>

                    <Button v-if="props.can?.create" @click="openCreate">
                        {{ $t('user.create') }}
                    </Button>
                </div>
            </div>
        </CardHeader>

        <CardContent>
            <UserFilters
                ref="userFilters"
                :url="adminUserRoutes.index().url"
                :initial="props.filters"
                :roles="roles"
                :creators="creators"
                :only="['users', 'usersMeta', 'stats', 'filters']"
            />

            <p class="mb-2 text-xs text-muted-foreground">
                <template v-if="totalCount">
                    {{ $t('common.resultRange', { from: rangeFrom, to: rangeTo, total: totalCount }) }}
                </template>
                <template v-else>
                    {{ $t('common.resultEmpty') }}
                </template>
            </p>

            <UserTable
                v-model:selected="selected"
                :users="items"
                :start-index="startIndex"
                :processing-id="processingId"
                :bulk-processing="bulkProcessing"
                storage-key="admin-user-columns"
                :default-columns="ADMIN_DEFAULT_USER_TABLE_COLUMNS"
                :table-actions="ADMIN_USER_TABLE_ACTIONS"
                @action="handleAction"
            />

            <TripPagination :links="links" :fallback-url="adminUserRoutes.index().url" />
        </CardContent>
    </Card>

    <!-- Dialog chính: tạo / sửa / xem -->
    <UserFormDialog
        v-model:open="mainOpen"
        :mode="mode"
        :user="activeUser"
        :roles="roles"
        :permissions="props.permissions ?? []"
        :can-assign-role="canAssignRole"
        @switch-mode="(m) => (mode = m)"
    />

    <UserDeleteDialog v-model:open="deleteOpen" :user="target" />
    <UserAccessControlDialog
        v-model:open="accessControlOpen"
        :roles="roles"
        :permissions="props.permissions ?? []"
    />

    <UserBulkDialog
        v-model:open="bulkOpen"
        :action="bulkAction"
        :count="selected.length"
        :processing="bulkProcessing"
        @confirm="runBulk"
    />
</template>