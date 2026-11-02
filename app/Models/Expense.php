<?php

namespace App\Models;

use App\Policies\ExpensePolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'name',
    'overkm_rate',
    'overtime_rate',
    'overnight_rate',
    'holiday_rate',
    'note',
    'is_active',
    'created_by',
])]
#[UsePolicy(ExpensePolicy::class)]
class Expense extends Model
{
    use HasFactory;

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'overkm_rate' => 'integer',
            'overtime_rate' => 'integer',
            'overnight_rate' => 'integer',
            'holiday_rate' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Expense $expense): void {
            if ($expense->tripExpenses()->exists()) {
                throw ValidationException::withMessages([
                    'expense' => 'Không thể xóa khoản chi phí đang được sử dụng trong chuyến đi.',
                ]);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tripExpenses(): HasMany
    {
        return $this->hasMany(TripExpense::class, 'expense_id');
    }
}
