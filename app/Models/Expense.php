<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory;
    protected $table = 'expenses';

    protected $fillable = [
                    'overkm_charge', 'overtime_charge','overnight_charge', 
                    'holiday_fee', 'note',
                    ];
    protected $attributes= ['is_active'=> 1];
    
}
