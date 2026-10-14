<script setup lang="ts">
import { CheckCircle2Icon, XCircleIcon, MailCheckIcon, MailXIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';

import { useFormat } from '@/composables/useFormat';
import { useStickyShadow } from '@/composables/useStickyShadow';
import { useTableColumns } from '@/composables/useTableColumns';

import { ADMIN_USER_TABLE_ACTIONS } from '@/lib/user-action-presets';
import type { AppUser, UserActionType } from '@/types/user';
import { USER_TABLE_COLUMNS, DEFAULT_USER_TABLE_COLUMNS } from '@/types/user-table';
import type { UserColumnKey } from '@/types/user-table';
import UserActions from './UserActions.vue';
import UserColumnPicker from './UserColumnPicker.vue';

const props = withDefaults(
    defineProps<{
        users: AppUser[];
        startIndex?: number;
        processingId?: number | null;
        storageKey?: string;
        defaultColumns?: UserColumnKey[];
        tableActions?: UserActionType[];
        selected?: number[];
        bulkProcessing?: boolean;
    }>(),
    {
        startIndex: 1,
        processingId: null,
        storageKey: 'user-table-visible-columns',
        // props kiểu mảng/đối tượng BẮT BUỘC dùng factory
        defaultColumns: () => DEFAULT_USER_TABLE_COLUMNS,
        tableActions: () => ADMIN_USER_TABLE_ACTIONS,
        selected: () => [],
        bulkProcessing: false,
    },
);

const emit = defineEmits<{
    (e: 'action', payload: { type: UserActionType; user: AppUser }): void;
    (e: 'update:selected', ids: number[]): void;
}>();

const { formatDateTime } = useFormat();

/* ---------- Cột hiển thị ---------- */
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
    columns: USER_TABLE_COLUMNS,
    storageKey: props.storageKey,
    defaultColumns: props.defaultColumns,
});

/* ---------- Sticky 2 trục ---------- */
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

/* ---------- Chọn hàng loạt ---------- */
const selectableIds = computed(() =>
    props.users.filter((u) => u.can?.delete || u.can?.toggleActive).map((u) => u.id),
);

const allSelected = computed(
    () => selectableIds.value.length > 0 && selectableIds.value.every((id) => props.selected.includes(id)),
);

const someSelected = computed(
    () => props.selected.length > 0 && !allSelected.value,
);

const toggleRow = (id: number, checked: boolean) => {
    const next = checked
        ? [...new Set([...props.selected, id])]
        : props.selected.filter((x) => x !== id);
    emit('update:selected', next);
};

const toggleAllRows = (checked: boolean) => {
    emit('update:selected', checked ? [...selectableIds.value] : []);
};

/* ---------- Helpers ---------- */
const roleVariant = (name: string) =>
    ({
        admin: 'bg-rose-600 text-white',
        manager: 'bg-indigo-600 text-white',
        editor: 'bg-sky-600 text-white',
        advisor: 'bg-emerald-600 text-white',
        driver: 'bg-amber-500 text-white',
    }[name] ?? 'bg-gray-500 text-white');

const isActive = (u: AppUser) => u.is_active === true || u.is_active === 1;

const fire = (type: UserActionType, user: AppUser) => emit('action', { type, user });
</script>

<template>
    <div class="space-y-3">
        <div class="flex justify-end">
            <UserColumnPicker
                :optional-columns="optionalColumns"
                :visible-column-count="visibleColumnCount"
                :total-column-count="USER_TABLE_COLUMNS.length"
                :selected-optional-count="selectedOptionalCount"
                :all-selected="allOptionalSelected"
                :is-visible="isVisible"
                @toggle="toggle"
                @toggle-all="toggleAll"
                @reset="resetToDefault"
            />
        </div>

        <div
            ref="scroller"
            class="relative max-h-[70vh] max-w-full overflow-auto rounded-lg border border-gray-300 dark:border-gray-700"
        >
            <table class="w-full table-auto border-separate border-spacing-0">
                <thead class="sticky top-0 z-30">
                    <tr class="bg-gray-100 dark:bg-gray-800">
                        <th
                            v-for="column in renderedColumns"
                            :key="column.key"
                            scope="col"
                            class="whitespace-nowrap border-b border-r border-gray-300 bg-gray-100 px-2 py-2 text-center align-middle text-sm font-semibold dark:border-gray-700 dark:bg-gray-800"
                            :class="[
                                column.key === 'select' || column.key === 'stt' ? stickyLeftHead : '',
                                column.key === 'actions' ? stickyRightHead : '',
                            ]"
                        >
                            <template v-if="column.key === 'select'">
                                <Checkbox
                                    :model-value="allSelected"
                                    :indeterminate="someSelected"
                                    :disabled="!selectableIds.length || bulkProcessing"
                                    :aria-label="$t('common.selectAll')"
                                    @update:model-value="(value) => toggleAllRows(value === true)"
                                />
                            </template>
                            <template v-else>{{ $t(column.labelKey) }}</template>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-if="!users.length">
                        <td
                            :colspan="visibleColumnCount"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            {{ $t('common.noData') }}
                        </td>
                    </tr>

                    <tr
                        v-for="(user, index) in users"
                        :key="user.id"
                        class="bg-white transition-colors hover:bg-gray-50 dark:bg-gray-950 dark:hover:bg-gray-900"
                        :class="selected.includes(user.id) ? 'bg-primary/5 dark:bg-primary/10' : ''"
                    >
                        <!-- Checkbox -->
                        <td
                            v-if="isVisible('select')"
                            class="px-2 py-2 text-center align-middle"
                            :class="[cellBorder, stickyLeftBody]"
                        >
                            <Checkbox
                                :model-value="selected.includes(user.id)"
                                :disabled="!selectableIds.includes(user.id) || bulkProcessing"
                                :aria-label="$t('user.actions.selectRow', { name: user.name })"
                                @update:model-value="(value) => toggleRow(user.id, value === true)"
                            />
                        </td>

                        <!-- STT -->
                        <td
                            v-if="isVisible('stt')"
                            class="px-2 py-2 text-center align-middle text-sm font-medium"
                            :class="[cellBorder, isVisible('select') ? '' : stickyLeftBody]"
                        >
                            <button
                                type="button"
                                class="inline-flex min-w-8 items-center justify-center rounded-md px-2 py-1 text-primary underline-offset-4 transition-colors hover:bg-primary/10 hover:underline focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1"
                                :title="$t('user.actions.viewNumber', { id: user.id })"
                                :aria-label="$t('user.actions.viewNumber', { id: user.id })"
                                @click="fire('view', user)"
                            >
                                {{ startIndex + index }}
                            </button>
                        </td>

                        <!-- Tên -->
                        <td
                            v-if="isVisible('name')"
                            class="max-w-[220px] truncate px-2 py-2 text-left align-middle font-medium"
                            :class="cellBorder"
                            :title="user.name"
                        >
                            {{ user.name }}
                        </td>

                        <!-- Email -->
                        <td
                            v-if="isVisible('email')"
                            class="max-w-[240px] truncate px-2 py-2 text-left align-middle"
                            :class="cellBorder"
                            :title="user.email"
                        >
                            {{ user.email }}
                        </td>

                        <td
                            v-if="isVisible('phone')"
                            class="whitespace-nowrap px-2 py-2 text-center align-middle"
                            :class="cellBorder"
                        >
                            {{ user.phone ?? '—' }}
                        </td>

                        <!-- Vai trò -->
                        <td
                            v-if="isVisible('roles')"
                            class="px-2 py-2 text-center align-middle"
                            :class="cellBorder"
                        >
                            <div class="flex flex-wrap items-center justify-center gap-1">
                                <Badge
                                    v-for="role in user.roles ?? []"
                                    :key="role.id"
                                    :class="roleVariant(role.name)"
                                >
                                    {{ $t(`role.${role.name}`) }}
                                </Badge>
                                <span v-if="!user.roles?.length" class="text-muted-foreground">-</span>
                            </div>
                        </td>

                        <!-- Trạng thái -->
                        <td
                            v-if="isVisible('is_active')"
                            class="px-2 py-2 align-middle"
                            :class="cellBorder"
                        >
                            <div class="flex items-center justify-center gap-1.5">
                                <CheckCircle2Icon v-if="isActive(user)" class="h-4 w-4 text-emerald-500" />
                                <XCircleIcon v-else class="h-4 w-4 text-gray-400" />
                                <span class="text-xs">
                                    {{ isActive(user) ? $t('user.status.active') : $t('user.status.inactive') }}
                                </span>
                            </div>
                        </td>

                        <!-- Xác minh email -->
                        <td
                            v-if="isVisible('email_verified_at')"
                            class="whitespace-nowrap px-2 py-2 align-middle"
                            :class="cellBorder"
                        >
                            <div class="flex items-center justify-center gap-1.5">
                                <MailCheckIcon v-if="user.email_verified_at" class="h-4 w-4 text-emerald-500" />
                                <MailXIcon v-else class="h-4 w-4 text-amber-500" />
                                <span class="text-xs text-muted-foreground">
                                    {{ user.email_verified_at ? formatDateTime(user.email_verified_at) : $t('user.status.unverified') }}
                                </span>
                            </div>
                        </td>

                        <!-- Người tạo -->
                        <td
                            v-if="isVisible('creator')"
                            class="whitespace-nowrap px-2 py-2 text-center align-middle"
                            :class="cellBorder"
                        >
                            {{ user.creator?.name ?? $t('user.system') }}
                        </td>

                        <!-- Ngày tạo -->
                        <td
                            v-if="isVisible('created_at')"
                            class="whitespace-nowrap px-2 py-2 text-center align-middle"
                            :class="cellBorder"
                        >
                            {{ formatDateTime(user.created_at) }}
                        </td>

                        <!-- Cập nhật -->
                        <td
                            v-if="isVisible('updated_at')"
                            class="whitespace-nowrap px-2 py-2 text-center align-middle"
                            :class="cellBorder"
                        >
                            {{ formatDateTime(user.updated_at) }}
                        </td>

                        <!-- Thao tác -->
                        <td
                            v-if="isVisible('actions')"
                            class="px-2 py-2 align-middle"
                            :class="[cellBorder, stickyRightBody]"
                        >
                            <UserActions
                                :user="user"
                                :only="tableActions"
                                :processing="processingId === user.id"
                                @action="emit('action', $event)"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>