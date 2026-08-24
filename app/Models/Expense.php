<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory;
    protected $table = 'expenses';

    protected $fillable = [ 
                    // 'name', 'type', 'unit_price',
                    'overkm_rate', 'overtime_rate','overnight_rate', 
                    'holiday_fee', 'note',
                    ];
    protected $attributes= ['is_active'=> 1];

    public function tripExpense()
    {
        return $this->hasOne(TripExpense::class, 'expense_id','id');
    }
    
}
