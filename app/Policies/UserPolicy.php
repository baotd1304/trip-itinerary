<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** Admin toàn quyền (trừ các chặn tự thao tác bên dưới). */
    public function before(User $authUser, string $ability): ?bool
    {
        if (in_array($ability, ['delete', 'toggleActive', 'assignRole'], true)) {
            return null;
        }

        return $authUser->hasRole('admin') ? true : null;
    }

    public function viewAny(User $authUser): bool
    {
        return $authUser->hasAnyRole(['admin', 'manager']);
    }

    public function view(User $authUser, User $target): bool
    {
        return $authUser->hasAnyRole(['admin', 'manager']) || $authUser->id === $target->id;
    }

    public function create(User $authUser): bool
    {
        return $authUser->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $authUser, User $target): bool
    {
        if ($authUser->id === $target->id) {
            return true; // tự sửa hồ sơ
        }

        // Manager không được sửa admin
        if ($target->hasRole('admin')) {
            return $authUser->hasRole('admin');
        }

        return $authUser->hasAnyRole(['admin', 'manager']);
    }

    public function delete(User $authUser, User $target): bool
    {
        if ($authUser->id === $target->id) {
            return false; // không tự xoá
        }

        if ($target->hasRole('admin')) {
            return false; // không xoá admin qua UI
        }

        return $authUser->hasAnyRole(['admin', 'manager']);
    }

    public function toggleActive(User $authUser, User $target): bool
    {
        if ($authUser->id === $target->id) {
            return false;
        }

        if ($target->hasRole('admin')) {
            return $authUser->hasRole('admin') && $authUser->id !== $target->id;
        }

        return $authUser->hasAnyRole(['admin', 'manager']);
    }

    /** Chỉ admin mới được đổi vai trò. */
    public function assignRole(User $authUser, User $target): bool
    {
        return $authUser->hasRole('admin') && $authUser->id !== $target->id;
    }

    public function verifyEmail(User $authUser, User $target): bool
    {
        return $authUser->hasAnyRole(['admin', 'manager']) && ! $target->email_verified_at;
    }

    public function resetPassword(User $authUser, User $target): bool
    {
        return $authUser->hasRole('admin');
    }

    public function manageAccessControl(User $authUser): bool
    {
        return $authUser->hasRole('admin');
    }
}
