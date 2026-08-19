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
                    'overtime', 'overtime_rate','overnight','overnight_rate', 'toll_fee', 'airport_fee',
                    'holiday_rate'
                ];
}
