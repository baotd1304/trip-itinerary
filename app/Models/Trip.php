<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Trip extends Model
{
    use HasFactory;
    protected $table = 'trips';
    protected $fillable = [
                    'advisor','car_id', 'driver', 'day', 'origin', 'destination',
                    'departure_time', 'arrival_time', 'odo_start', 'odo_end', 'distance',
                    'status', 'total_fee', 'note',
                    ];
    protected $attributes= ['status'=> 'pending', 'distance'=>0];
    
    protected function casts(): array
    {
        return [
            'day'       => 'date',
            'total_fee' => 'decimal:0',
        ];
    }
    public function car()
    {
        return $this->belongsTo(Car::class);
    }
    public function tripExpense()
    {
        return $this->hasOne(TripExpense::class,'trip_id','id');
    }

    /* ------------------------ Scopes dùng cho export ------------------------ */

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeOfCar(Builder $query, ?int $carId): Builder
    {
        return $query->when($carId, fn ($q) => $q->where('car_id', $carId));
    }

    /** @param  string|Carbon  $month  Định dạng 'Y-m' */
    public function scopeOfMonth(Builder $query, string|Carbon $month): Builder
    {
        $date = $month instanceof Carbon
            ? $month->copy()
            : Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        return $query->whereBetween('day', [
            $date->copy()->startOfMonth()->toDateString(),
            $date->copy()->endOfMonth()->toDateString(),
        ]);
    }
    
}
