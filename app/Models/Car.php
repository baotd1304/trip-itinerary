<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['name', 'brand', 'model', 'year', 'license_plate', 'owner', 'status'])]

class Car extends Model
{
    use HasFactory, Notifiable;

    protected $attributes = [
        'status' => 1,
    ];
}
