<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Policies\TripPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsePolicy(TripPolicy::class)]
class Trip extends Model
{
    use HasFactory;
    protected $table = 'trips';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_EDITTING  = 'editting';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED  = 'rejected';

    /** Trạng thái mà driver được phép SỬA */
    public const EDITABLE_STATUSES = [self::STATUS_PENDING, self::STATUS_EDITTING];

    /** Trạng thái mà driver được phép XOÁ */
    public const DELETABLE_STATUSES = [self::STATUS_PENDING];

    /** Trạng thái khoá cứng với driver */
    public const LOCKED_STATUSES = [self::STATUS_CONFIRMED];

    /** Trạng thái có thể mở khoá cho driver sửa lại */
    public const REOPENABLE_STATUSES = [self::STATUS_CONFIRMED, self::STATUS_REJECTED];
    protected $fillable = [
                    'advisor_id', 'driver_id', 'car_id', 'day', 'origin', 'destination',
                    'departure_time', 'arrival_time', 'odo_start', 'odo_end', 'distance',
                    'status', 'total_fee', 'note', 'reopen_reason', 'reopened_by', 'reopened_at'
                ];
    protected $attributes= ['status'=> 'pending', 'distance'=>0];
    
    protected function casts(): array
    {
        return [
            'day'       => 'date',
            'total_fee' => 'decimal:0',
            'reopened_at' => 'datetime',
        ];
    }

    public function isLocked(): bool
    {
        return in_array($this->status, self::LOCKED_STATUSES, true);
    }
    public function isReopenable(): bool
    {
        return in_array($this->status, self::REOPENABLE_STATUSES, true);
    }

    /** Chuyển sang trạng thái cho phép driver sửa */
    public function reopenFor(User $actor, string $reason): void
    {
        $this->forceFill([
            'status'        => self::STATUS_EDITTING,
            'reopen_reason' => $reason,
            'reopened_by'   => $actor->id,
            'reopened_at'   => now(),
        ])->save();
    }
    public function reopener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }
    public function images(): HasMany
    {
        return $this->hasMany(TripImage::class)->orderBy('sort_order');
    }
    public function car()
    {
        return $this->belongsTo(Car::class);
    }
    public function advisor()
    {
        return $this->belongsTo(User::class, 'advisor_id');
    }
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
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
