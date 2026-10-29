<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { adminUserRoutes } from '@/lib/user-routes-admin';
import type { AppUser, PermissionRef, RoleRef, UserDialogMode } from '@/types/user';

const props = defineProps<{
    open: boolean;
    mode: UserDialogMode;
    user: AppUser | null;
    roles: RoleRef[];
    permissions: PermissionRef[];
    canAssignRole?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:open', v: boolean): void;
    (e: 'switch-mode', m: UserDialogMode): void;
}>();

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    is_active: true,
    mark_verified: false,
    roles: [] as string[],
    permissions: [] as string[],
});

const isView = computed(() => props.mode === 'view');
const isCreate = computed(() => props.mode === 'create');
const sortedRoles = computed(() => {
    const roleOrder: Record<string, number> = {
        admin: 0,
        manager: 1,
        editor: 2,
        advisor: 3,
        driver: 4,
        partner: 5,
    };

    return [...props.roles].sort((first, second) => {
        const orderDifference = (roleOrder[first.name] ?? Number.MAX_SAFE_INTEGER)
            - (roleOrder[second.name] ?? Number.MAX_SAFE_INTEGER);

        return orderDifference || first.name.localeCompare(second.name);
    });
});
const permissionGroups = computed(() => {
    const groups = new Map<string, PermissionRef[]>();

    for (const permission of props.permissions) {
        const [groupName = 'other'] = permission.name.split('.');
        const groupPermissions = groups.get(groupName) ?? [];
        groupPermissions.push(permission);
        groups.set(groupName, groupPermissions);
    }

    return [...groups.entries()]
        .sort(([first], [second]) => first.localeCompare(second))
        .map(([name, groupPermissions]) => ({
            name,
            permissions: groupPermissions.sort((first, second) => first.name.localeCompare(second.name)),
        }));
});
const canEditAccess = computed(() =>
    props.canAssignRole === true && (isCreate.value || props.user?.can?.assignRole === true),
);
const canSetActive = computed(() =>
    isCreate.value || props.user?.can?.toggleActive === true,
);
const canSetVerified = computed(() =>
    isCreate.value || props.user?.can?.verifyEmail === true,
);
const canSetPassword = computed(() =>
    isCreate.value || props.user?.can?.resetPassword === true,
);

watch(
    () => [props.open, props.user, props.mode],
    () => {
        if (!props.open) {
return;
}

        form.clearErrors();
        form.defaults({
            name: props.user?.name ?? '',
            email: props.user?.email ?? '',
            phone: props.user?.phone ?? '',
            password: '',
            password_confirmation: '',
            is_active: props.user ? props.user.is_active === true || props.user.is_active === 1 : true,
            mark_verified: !!props.user?.email_verified_at,
            roles: (props.user?.roles ?? []).map((r) => r.name),
            permissions: props.user?.direct_permissions ?? [],
        });
        form.reset();
    },
    { immediate: true },
);

form.transform((data) => {
    return {
        ...data,
        roles: canEditAccess.value ? data.roles : undefined,
        permissions: canEditAccess.value ? data.permissions : undefined,
        is_active: canSetActive.value ? data.is_active : undefined,
        mark_verified: canSetVerified.value ? data.mark_verified : undefined,
        password: canSetPassword.value ? data.password : undefined,
        password_confirmation: canSetPassword.value ? data.password_confirmation : undefined,
    };
});

const toggleRole = (name: string, checked: boolean) => {
    form.roles = checked
        ? [...new Set([...form.roles, name])]
        : form.roles.filter((r) => r !== name);
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    };

    if (isCreate.value) {
        form.post(adminUserRoutes.store().url, options);

        return;
    }

    if (props.user) {
        form.put(adminUserRoutes.update(props.user.id).url, options);
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="flex max-h-[90vh] flex-col sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>
                    {{ isCreate ? $t('user.dialog.createTitle') : isView ? $t('user.dialog.viewTitle') : $t('user.dialog.editTitle') }}
                </DialogTitle>
                <DialogDescription>{{ $t('user.dialog.description') }}</DialogDescription>
            </DialogHeader>

            <div class="grid flex-1 gap-4 overflow-y-auto px-1 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <Label for="u-name">{{ $t('user.fields.name') }}</Label>
                    <Input id="u-name" v-model="form.name" :disabled="isView" maxlength="255" />
                    <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                </div>

                <div class="space-y-1.5">
                    <Label for="u-email">{{ $t('user.fields.email') }}</Label>
                    <Input id="u-email" v-model="form.email" type="email" :disabled="isView" maxlength="255" />
                    <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                </div>

                <div class="space-y-1.5">
                    <Label for="u-phone">{{ $t('user.fields.phone') }}</Label>
                    <Input id="u-phone" v-model="form.phone" type="tel" :disabled="isView" maxlength="20" />
                    <p v-if="form.errors.phone" class="text-xs text-destructive">{{ form.errors.phone }}</p>
                </div>

                <template v-if="!isView">
                    <div v-if="canSetPassword" class="space-y-1.5">
                        <Label for="u-pass">
                            {{ $t('user.fields.password') }}
                            <span v-if="!isCreate" class="text-xs text-muted-foreground">({{ $t('user.dialog.passwordHint') }})</span>
                        </Label>
                        <Input id="u-pass" v-model="form.password" type="password" autocomplete="new-password" />
                        <p v-if="form.errors.password" class="text-xs text-destructive">{{ form.errors.password }}</p>
                    </div>

                    <div v-if="canSetPassword" class="space-y-1.5">
                        <Label for="u-pass2">{{ $t('user.fields.passwordConfirm') }}</Label>
                        <Input id="u-pass2" v-model="form.password_confirmation" type="password" autocomplete="new-password" />
                    </div>
                </template>

                <div class="space-y-1.5 sm:col-span-2">
                    <Label>{{ $t('user.fields.roles') }}</Label>
                    <div class="grid grid-cols-2 gap-1.5 rounded-md border p-2 sm:gap-2 sm:p-3">
                        <label
                            v-for="role in sortedRoles"
                            :key="role.id"
                            class="flex min-w-0 cursor-pointer items-center gap-2 rounded-md border p-2 text-xs transition-colors hover:bg-muted/50 sm:gap-3 sm:p-3 sm:text-sm"
                            :class="form.roles.includes(role.name) ? 'border-primary bg-primary/5' : 'border-border'"
                        >
                            <Checkbox
                                :model-value="form.roles.includes(role.name)"
                                :disabled="isView || !canEditAccess"
                                @update:model-value="(v: boolean | 'indeterminate') => toggleRole(role.name, v === true)"
                            />
                            <span class="truncate">{{ $t(`role.${role.name}`) }}</span>
                        </label>
                    </div>
                    <p v-if="form.errors.roles" class="text-xs text-destructive">{{ form.errors.roles }}</p>
                </div>

                <div v-if="canEditAccess" class="space-y-1.5 sm:col-span-2">
                    <Label>{{ $t('user.fields.directPermissions') }}</Label>
                    <div class="max-h-[35vh] space-y-3 overflow-y-auto rounded-md border p-2 sm:max-h-64 sm:space-y-4 sm:p-3">
                        <section
                            v-for="group in permissionGroups"
                            :key="group.name"
                            class="space-y-1.5"
                        >
                            <h3 class="border-b pb-1 text-[0.7rem] font-semibold uppercase tracking-wide text-muted-foreground">
                                {{ group.name }}
                            </h3>
                            <div class="grid gap-1 sm:grid-cols-2 sm:gap-2">
                                <label
                                    v-for="permission in group.permissions"
                                    :key="permission.id"
                                    class="flex min-w-0 cursor-pointer items-center gap-2 rounded-md border border-transparent px-1.5 py-1 text-xs transition-colors hover:border-border hover:bg-muted/50 sm:px-2 sm:py-1.5 sm:text-sm"
                                    :class="form.permissions.includes(permission.name) ? 'bg-primary/5 text-foreground' : ''"
                                >
                                    <Checkbox
                                        :model-value="form.permissions.includes(permission.name)"
                                        :disabled="isView"
                                        @update:model-value="(value: boolean | 'indeterminate') => {
                                            form.permissions = value === true
                                                ? [...new Set([...form.permissions, permission.name])]
                                                : form.permissions.filter((name) => name !== permission.name);
                                        }"
                                    />
                                    <span class="truncate">{{ permission.name }}</span>
                                </label>
                            </div>
                        </section>
                        <p v-if="!permissions.length" class="text-sm text-muted-foreground">
                            {{ $t('user.accessControl.noPermissions') }}
                        </p>
                    </div>
                    <p v-if="form.errors.permissions" class="text-xs text-destructive">{{ form.errors.permissions }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <Switch id="u-active" v-model="form.is_active" :disabled="isView || !canSetActive" />
                    <Label for="u-active">{{ $t('user.fields.isActive') }}</Label>
                </div>

                <div class="flex items-center gap-3">
                    <Switch id="u-verified" v-model="form.mark_verified" :disabled="isView || !canSetVerified" />
                    <Label for="u-verified">{{ $t('user.fields.markVerified') }}</Label>
                </div>

                <div v-if="isView && user" class="sm:col-span-2 grid gap-2 rounded-md bg-muted/40 p-3 text-xs text-muted-foreground sm:grid-cols-2">
                    <div>{{ $t('user.fields.createdBy') }}: <b>{{ user.creator?.name ?? $t('user.system') }}</b></div>
                    <div>{{ $t('user.fields.phone') }}: <b>{{ user.phone ?? '—' }}</b></div>
                    <div>{{ $t('user.fields.createdAt') }}: <b>{{ user.created_at }}</b></div>
                    <div>{{ $t('user.fields.updatedAt') }}: <b>{{ user.updated_at }}</b></div>
                    <div>{{ $t('user.fields.verified') }}: <b>{{ user.email_verified_at ?? $t('user.status.unverified') }}</b></div>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)">{{ $t('common.close') }}</Button>

                <Button v-if="isView && user?.can?.update" @click="emit('switch-mode', 'edit')">
                    {{ $t('common.edit') }}
                </Button>

                <Button v-if="!isView" :disabled="form.processing" @click="submit">
                    {{ isCreate ? $t('common.create') : $t('common.save') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>