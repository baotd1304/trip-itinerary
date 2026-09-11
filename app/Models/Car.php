<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'brand', 'model', 'year', 'license_plate', 'owner', 'is_active'])]

class Car extends Model
{
    use HasFactory, Notifiable;

    protected $attributes = [
        'is_active' => 1,
    ];
    protected static function boot()
    {
        parent::boot();
        
        // static::deleting(function ($car) {
        //     if ($car->trips()->exists()) {
        //         throw new \Exception('Không thể xóa xe này vì còn dữ liệu chuyến đi liên quan. 
        //                     Vui lòng chuyển giao hoặc xóa chuyến đi trước.');
        //     }
        // });

        static::deleting(function ($car) {
            if ($car->trips()->exists()) {
                throw ValidationException::withMessages([
                    'car' => 'Không thể xóa xe này vì còn dữ liệu chuyến đi liên quan.'
                ]);
            }
        });
    }
    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

}
