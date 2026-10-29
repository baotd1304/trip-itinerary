<?php

namespace App\Models;

use App\Policies\CarPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'brand', 'model', 'year', 'license_plate', 'owner', 'is_active'])]
#[UsePolicy(CarPolicy::class)]
class Car extends Model
{
    use HasFactory, Notifiable;

    protected $attributes = [
        'is_active' => 1,
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Car $car): void {
            if ($car->trips()->exists()) {
                throw ValidationException::withMessages([
                    'car' => 'Không thể xóa xe này vì còn dữ liệu chuyến đi liên quan.',
                ]);
            }
        });
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
