<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    private const PERMISSIONS = [
        'viewAny' => 'users.view-any',
        'view' => 'users.view',
        'create' => 'users.create',
        'createAdmin' => 'users.create-admin',
        'update' => 'users.update',
        'updateAdmin' => 'users.update-admin',
        'delete' => 'users.delete',
        'deleteAdmin' => 'users.delete-admin',
        'toggleActive' => 'users.toggle-active',
        'toggleAdminActive' => 'users.toggle-admin-active',
        'assignRole' => 'users.assign-role',
        'assignRoleAdmin' => 'users.assign-role-admin',
        'verifyEmail' => 'users.verify-email',
        'resetPassword' => 'users.reset-password',
        'manageAccessControl' => 'users.manage-access-control',
    ];

    /** Admin toàn quyền (trừ các chặn tự thao tác bên dưới). */
    public function before(User $authUser, string $ability): ?bool
    {
        if (in_array($ability, ['delete', 'toggleActive', 'assignRole'], true)) {
            return null;
        }

        return $authUser->hasRole('admin') ? true : null;
    }

    private function hasPermission(User $authUser, string $ability): bool
    {
        $permission = self::PERMISSIONS[$ability] ?? null;

        return $permission !== null
            && $authUser->can($permission);
    }

    private function canManageTarget(
        User $authUser,
        User $target,
        string $ability,
        string $adminAbility,
    ): bool {
        if ($authUser->hasRole('admin')) {
            return true;
        }

        return $this->hasPermission(
            $authUser,
            $target->hasRole('admin') ? $adminAbility : $ability,
        );
    }

    public function viewAny(User $authUser): bool
    {
        return $this->hasPermission($authUser, 'viewAny');
    }

    public function view(User $authUser, User $target): bool
    {
        return $authUser->id === $target->id
            || $this->hasPermission($authUser, 'view');
    }

    public function create(User $authUser): bool
    {
        return $this->hasPermission($authUser, 'create');
    }

    public function createAdmin(User $authUser): bool
    {
        return $this->hasPermission($authUser, 'createAdmin');
    }

    public function update(User $authUser, User $target): bool
    {
        if ($authUser->id === $target->id) {
            return true; // tự sửa hồ sơ cá nhân
        }

        return $this->canManageTarget($authUser, $target, 'update', 'updateAdmin');
    }

    public function delete(User $authUser, User $target): bool
    {
        if ($authUser->id === $target->id) {
            return false; // không tự xoá
        }

        return $this->canManageTarget($authUser, $target, 'delete', 'deleteAdmin');
    }

    public function toggleActive(User $authUser, User $target): bool
    {
        if ($authUser->id === $target->id) {
            return false;
        }

        return $this->canManageTarget($authUser, $target, 'toggleActive', 'toggleAdminActive');
    }

    public function assignRole(User $authUser, User $target): bool
    {
        if ($authUser->id === $target->id) {
            return false;
        }

        return $this->canManageTarget($authUser, $target, 'assignRole', 'assignRoleAdmin');
    }

    public function verifyEmail(User $authUser, User $target): bool
    {
        return ! $target->email_verified_at
            && ($authUser->hasRole('admin') || $this->hasPermission($authUser, 'verifyEmail'));
    }

    public function resetPassword(User $authUser, User $target): bool
    {
        return $this->hasPermission($authUser, 'resetPassword');
    }

    public function manageAccessControl(User $authUser): bool
    {
        return $this->hasPermission($authUser, 'manageAccessControl');
    }
}
