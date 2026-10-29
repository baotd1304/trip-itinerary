<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    private const PERMISSIONS = [
        'viewAny' => 'expenses.view-any',
        'view' => 'expenses.view',
        'create' => 'expenses.create',
        'update' => 'expenses.update',
        'delete' => 'expenses.delete',
        'toggleActive' => 'expenses.toggle-active',
    ];

    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    private function hasPermission(User $user, string $ability): bool
    {
        return isset(self::PERMISSIONS[$ability])
            && $user->can(self::PERMISSIONS[$ability]);
    }

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'viewAny');
    }

    public function view(User $user, Expense $expense): bool
    {
        return $this->hasPermission($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $this->hasPermission($user, 'update');
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $this->hasPermission($user, 'delete');
    }

    public function deleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'delete');
    }

    public function toggleActive(User $user, Expense $expense): bool
    {
        return $this->hasPermission($user, 'toggleActive');
    }

    public function toggleActiveAny(User $user): bool
    {
        return $this->hasPermission($user, 'toggleActive');
    }
}
