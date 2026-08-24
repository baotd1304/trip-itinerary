<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TripExpense extends Model
{
    use HasFactory;
    protected $table = 'trip_expense';
    protected $fillable = [
                    'trip_id', 'expense_id', 
                    // 'quantity', 'unit_price', 'subtotal',
                    'overtime', 'overtime_rate','is_overnight','overnight_rate', 'toll_fee', 'airport_fee',
                    'is_holiday', 'holiday_rate'
                ];
    protected $casts = [
        'is_overnight' => 'boolean',
        'is_holiday' => 'boolean',
    ];
    protected $attributes= ['is_holiday'=> 0, 'is_overnight'=>0];
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
