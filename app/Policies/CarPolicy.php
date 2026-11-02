<?php

namespace App\Policies;

use App\Models\Car;
use App\Models\User;

class CarPolicy
{
    private const PERMISSIONS = [
        'viewAny' => 'cars.view-any',
        'view' => 'cars.view',
        'create' => 'cars.create',
        'update' => 'cars.update',
        'delete' => 'cars.delete',
        'toggleActive' => 'cars.toggle-active',
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

    public function view(User $user, Car $car): bool
    {
        return $this->hasPermission($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create');
    }

    public function update(User $user, Car $car): bool
    {
        return $this->hasPermission($user, 'update');
    }

    public function delete(User $user, Car $car): bool
    {
        return $this->hasPermission($user, 'delete');
    }

    public function deleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'delete');
    }

    public function toggleActive(User $user, Car $car): bool
    {
        return $this->hasPermission($user, 'toggleActive');
    }

    public function toggleActiveAny(User $user): bool
    {
        return $this->hasPermission($user, 'toggleActive');
    }
}
