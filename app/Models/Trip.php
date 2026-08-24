<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trip extends Model
{
    use HasFactory;
    protected $table = 'trips';
    protected $fillable = [
                    'advisor','car_id', 'driver', 'day', 'origin', 'destination',
                    'departure_time', 'arrival_time', 'odo_start', 'odo_end', 'distance',
                    'status', 'total_fee',
                    'note',
                    ];
    protected $attributes= ['status'=> 0, 'distance'=>0];

   
    
    public function tripExpense()
    {
        return $this->hasOne(TripExpense::class,'trip_id','id');
    }
    
}
