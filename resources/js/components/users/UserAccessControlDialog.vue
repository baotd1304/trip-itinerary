<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { adminUserRoutes } from '@/lib/user-routes-admin';
import type { PermissionRef, RoleRef } from '@/types/user';

const props = defineProps<{
    open: boolean;
    roles: RoleRef[];
    permissions: PermissionRef[];
}>();

const emit = defineEmits<{ (e: 'update:open', value: boolean): void }>();

const roleForm = useForm({
    name: '',
    permissions: [] as string[],
});
const permissionForm = useForm({ name: '' });
const editingRoleId = ref<number | null>(null);
const editingPermissionId = ref<number | null>(null);
const deletingId = ref<number | null>(null);
const pendingDelete = ref<{ type: 'role' | 'permission'; id: number; name: string } | null>(null);
const editingAdminRole = computed(() =>
    props.roles.find((role) => role.id === editingRoleId.value)?.name === 'admin',
);

const resetRoleForm = () => {
    editingRoleId.value = null;
    roleForm.defaults({ name: '', permissions: [] });
    roleForm.reset();
    roleForm.clearErrors();
};

const resetPermissionForm = () => {
    editingPermissionId.value = null;
    permissionForm.defaults({ name: '' });
    permissionForm.reset();
    permissionForm.clearErrors();
};

const editRole = (role: RoleRef) => {
    editingRoleId.value = role.id;
    roleForm.defaults({
        name: role.name,
        permissions: (role.permissions ?? []).map((permission) => permission.name),
    });
    roleForm.reset();
    roleForm.clearErrors();
};

const submitRole = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            resetRoleForm();
        },
    };

    if (editingRoleId.value) {
        roleForm.put(adminUserRoutes.updateRole(editingRoleId.value).url, options);

        return;
    }

    roleForm.post(adminUserRoutes.storeRole().url, options);
};

const editPermission = (permission: PermissionRef) => {
    editingPermissionId.value = permission.id;
    permissionForm.defaults({ name: permission.name });
    permissionForm.reset();
    permissionForm.clearErrors();
};

const submitPermission = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            resetPermissionForm();
        },
    };

    if (editingPermissionId.value) {
        permissionForm.put(adminUserRoutes.updatePermission(editingPermissionId.value).url, options);

        return;
    }

    permissionForm.post(adminUserRoutes.storePermission().url, options);
};

const togglePermission = (name: string, checked: boolean) => {
    roleForm.permissions = checked
        ? [...new Set([...roleForm.permissions, name])]
        : roleForm.permissions.filter((permission) => permission !== name);
};

const deleteRole = (role: RoleRef) => {
    if (role.name === 'admin' || deletingId.value !== null) {
return;
}

    pendingDelete.value = { type: 'role', id: role.id, name: role.name };
};

const deletePermission = (permission: PermissionRef) => {
    if (deletingId.value !== null) {
return;
}

    pendingDelete.value = { type: 'permission', id: permission.id, name: permission.name };
};

const confirmDelete = () => {
    const pending = pendingDelete.value;

    if (!pending || deletingId.value !== null) {
return;
}

    deletingId.value = pending.id;
    const url = pending.type === 'role'
        ? adminUserRoutes.destroyRole(pending.id).url
        : adminUserRoutes.destroyPermission(pending.id).url;

    router.delete(url, {
        preserveScroll: true,
        onSuccess: () => {
            if (pending.type === 'role' && editingRoleId.value === pending.id) {
resetRoleForm();
}

            if (pending.type === 'permission' && editingPermissionId.value === pending.id) {
resetPermissionForm();
}

            pendingDelete.value = null;
        },
        onFinish: () => {
 deletingId.value = null; 
},
    });
};

watch(() => props.open, (open) => {
    if (open) {
return;
}

    resetRoleForm();
    resetPermissionForm();
    pendingDelete.value = null;
});
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-5xl">
            <DialogHeader>
                <DialogTitle>{{ $t('user.accessControl.title') }}</DialogTitle>
                <DialogDescription>{{ $t('user.accessControl.description') }}</DialogDescription>
            </DialogHeader>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="space-y-4">
                    <div class="space-y-1">
                        <h3 class="font-semibold">{{ $t('user.accessControl.roles') }}</h3>
                        <p class="text-sm text-muted-foreground">{{ $t('user.accessControl.roleDescription') }}</p>
                    </div>

                    <div class="space-y-2 rounded-md border p-3">
                        <div
                            v-for="role in roles"
                            :key="role.id"
                            class="flex items-center justify-between gap-2"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ role.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ $t('user.accessControl.permissionCount', { count: role.permissions?.length ?? 0 }) }}
                                </p>
                            </div>
                            <div class="flex shrink-0 gap-1">
                                <Button type="button" variant="ghost" size="sm" @click="editRole(role)">
                                    {{ $t('common.edit') }}
                                </Button>
                                <Button
                                    v-if="role.name !== 'admin'"
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive"
                                    :disabled="deletingId !== null"
                                    @click="deleteRole(role)"
                                >
                                    {{ $t('common.delete') }}
                                </Button>
                            </div>
                        </div>
                        <p v-if="!roles.length" class="text-sm text-muted-foreground">
                            {{ $t('user.accessControl.noRoles') }}
                        </p>
                    </div>

                    <form class="space-y-3 rounded-md border p-3" @submit.prevent="submitRole">
                        <div class="space-y-1.5">
                            <Label for="access-role-name">{{ $t('user.accessControl.roleName') }}</Label>
                            <Input id="access-role-name" v-model="roleForm.name" maxlength="100" :disabled="editingAdminRole" />
                            <p v-if="editingAdminRole" class="text-xs text-muted-foreground">
                                {{ $t('user.accessControl.reservedAdminRole') }}
                            </p>
                            <p v-if="roleForm.errors.name" class="text-xs text-destructive">{{ roleForm.errors.name }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label>{{ $t('user.accessControl.permissions') }}</Label>
                            <div class="flex max-h-44 flex-wrap gap-3 overflow-y-auto rounded-md border p-3">
                                <label
                                    v-for="permission in permissions"
                                    :key="permission.id"
                                    class="flex cursor-pointer items-center gap-2 text-sm"
                                >
                                    <Checkbox
                                        :model-value="roleForm.permissions.includes(permission.name)"
                                        @update:model-value="(checked) => togglePermission(permission.name, checked === true)"
                                    />
                                    {{ permission.name }}
                                </label>
                                <p v-if="!permissions.length" class="text-sm text-muted-foreground">
                                    {{ $t('user.accessControl.noPermissions') }}
                                </p>
                            </div>
                            <p v-if="roleForm.errors.permissions" class="text-xs text-destructive">{{ roleForm.errors.permissions }}</p>
                        </div>
                        <div class="flex justify-end gap-2">
                            <Button v-if="editingRoleId" type="button" variant="outline" @click="resetRoleForm">
                                {{ $t('common.cancel') }}
                            </Button>
                            <Button type="submit" :disabled="roleForm.processing">
                                {{ editingRoleId ? $t('common.save') : $t('user.accessControl.createRole') }}
                            </Button>
                        </div>
                    </form>
                </section>

                <section class="space-y-4">
                    <div class="space-y-1">
                        <h3 class="font-semibold">{{ $t('user.accessControl.permissions') }}</h3>
                        <p class="text-sm text-muted-foreground">{{ $t('user.accessControl.permissionDescription') }}</p>
                    </div>

                    <div class="max-h-64 space-y-2 overflow-y-auto rounded-md border p-3">
                        <div
                            v-for="permission in permissions"
                            :key="permission.id"
                            class="flex items-center justify-between gap-2"
                        >
                            <span class="min-w-0 truncate text-sm">{{ permission.name }}</span>
                            <div class="flex shrink-0 gap-1">
                                <Button type="button" variant="ghost" size="sm" @click="editPermission(permission)">
                                    {{ $t('common.edit') }}
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive"
                                    :disabled="deletingId !== null"
                                    @click="deletePermission(permission)"
                                >
                                    {{ $t('common.delete') }}
                                </Button>
                            </div>
                        </div>
                        <p v-if="!permissions.length" class="text-sm text-muted-foreground">
                            {{ $t('user.accessControl.noPermissions') }}
                        </p>
                    </div>

                    <form class="space-y-3 rounded-md border p-3" @submit.prevent="submitPermission">
                        <div class="space-y-1.5">
                            <Label for="access-permission-name">{{ $t('user.accessControl.permissionName') }}</Label>
                            <Input id="access-permission-name" v-model="permissionForm.name" maxlength="100" />
                            <p v-if="permissionForm.errors.name" class="text-xs text-destructive">{{ permissionForm.errors.name }}</p>
                        </div>
                        <div class="flex justify-end gap-2">
                            <Button v-if="editingPermissionId" type="button" variant="outline" @click="resetPermissionForm">
                                {{ $t('common.cancel') }}
                            </Button>
                            <Button type="submit" :disabled="permissionForm.processing">
                                {{ editingPermissionId ? $t('common.save') : $t('user.accessControl.createPermission') }}
                            </Button>
                        </div>
                    </form>
                </section>
            </div>

            <div v-if="pendingDelete" class="flex flex-wrap items-center justify-between gap-3 rounded-md border border-destructive/40 bg-destructive/5 p-3">
                <p class="text-sm">
                    {{ $t('user.accessControl.deleteConfirmation', { name: pendingDelete.name }) }}
                </p>
                <div class="flex gap-2">
                    <Button type="button" variant="outline" :disabled="deletingId !== null" @click="pendingDelete = null">
                        {{ $t('common.cancel') }}
                    </Button>
                    <Button type="button" variant="destructive" :disabled="deletingId !== null" @click="confirmDelete">
                        {{ deletingId !== null ? $t('common.saving') : $t('common.delete') }}
                    </Button>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="emit('update:open', false)">
                    {{ $t('common.close') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
