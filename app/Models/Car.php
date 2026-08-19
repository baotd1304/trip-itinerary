<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['name', 'brand', 'model', 'year', 'license_plate', 'owner', 'is_active'])]

class Car extends Model
{
    use HasFactory, Notifiable;

    protected $attributes = [
        'is_active' => 1,
    ];
}
