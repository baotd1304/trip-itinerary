<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Policies\TripPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\TripReopenRequest;

#[UsePolicy(TripPolicy::class)]
class Trip extends Model
{
    use HasFactory;
    protected $table = 'trips';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_EDITING  = 'editing';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED  = 'rejected';

    /** Driver được SỬA khi ở các trạng thái này */
    public const DRIVER_EDITABLE_STATUSES = [ 
        self::STATUS_PENDING,
        self::STATUS_EDITING,
        self::STATUS_REJECTED,
    ];
    /** Driver được XOÁ khi ở các trạng thái này */
    public const DRIVER_DELETABLE_STATUSES = [self::STATUS_PENDING];

    /** Driver được GỬI YÊU CẦU mở khoá khi ở các trạng thái này */
    public const REOPEN_REQUESTABLE_STATUSES = [self::STATUS_CONFIRMED];

    /** Advisor được DUYỆT (confirm/reject) khi ở các trạng thái này */
    public const REVIEWABLE_STATUSES = [self::STATUS_PENDING];
    
    /** Trạng thái khoá cứng với driver */
    public const DRIVER_LOCKED_STATUSES = [self::STATUS_CONFIRMED];

    protected $fillable = [
                    'advisor_id', 'driver_id', 'car_id', 'day', 'origin', 'destination',
                    'departure_time', 'arrival_time', 'odo_start', 'odo_end', 'distance',
                    'status', 'total_fee', 'note',
                    'reject_reason', 'submitted_at', 'reviewed_at', 'reviewed_by',
                ];
    protected $attributes= ['status'=> 'pending', 'distance'=>0];
    
    protected function casts(): array
    {
        return [
            'day'       => 'date',
            'total_fee' => 'decimal:0',
            'submitted_at' => 'datetime',
            'reviewed_at'  => 'datetime',
        ];
    }

    /* ============ Trạng thái ============ */
    public function isDriverEditable(): bool
    {
        return in_array($this->status, self::DRIVER_EDITABLE_STATUSES, true);
    }

    public function isDriverDeletable(): bool
    {
        return in_array($this->status, self::DRIVER_DELETABLE_STATUSES, true);
    }

    public function canRequestReopen(): bool
    {
        return in_array($this->status, self::REOPEN_REQUESTABLE_STATUSES, true);
    }

    public function isReviewable(): bool
    {
        return in_array($this->status, self::REVIEWABLE_STATUSES, true);
    }

    public function isLockedForDriver(): bool
    {
        return in_array($this->status, self::DRIVER_LOCKED_STATUSES, true);
    }

    /* ============ Chuyển trạng thái ============ */

    /** Driver sửa xong & submit -> chờ advisor duyệt */
    public function markSubmitted(): void
    {
        $this->forceFill([
            'status'        => self::STATUS_PENDING,
            'reject_reason' => null,
            'submitted_at'  => now(),
            'reviewed_at'   => null,
            'reviewed_by'   => null,
        ])->save();
    }

    /** Advisor/Admin duyệt yêu cầu reopen -> cho phép driver sửa */
    public function markEditing(): void
    {
        $this->forceFill(['status' => self::STATUS_EDITING])->save();
    }

    public function markConfirmed(User $reviewer): void
    {
        $this->forceFill([
            'status'        => self::STATUS_CONFIRMED,
            'reject_reason' => null,
            'reviewed_by'   => $reviewer->id,
            'reviewed_at'   => now(),
        ])->save();
    }

    public function markRejected(User $reviewer, string $reason): void
    {
        $this->forceFill([
            'status'        => self::STATUS_REJECTED,
            'reject_reason' => $reason,
            'reviewed_by'   => $reviewer->id,
            'reviewed_at'   => now(),
        ])->save();
    }

    /* ============ Quan hệ ============ */

    public function reopenRequests(): HasMany
    {
        return $this->hasMany(TripReopenRequest::class)->latest('id');
    }

    /** Yêu cầu reopen đang chờ duyệt (nếu có) */
    public function pendingReopenRequest(): HasOne
    {
        return $this->hasOne(TripReopenRequest::class)
            ->where('status', TripReopenRequest::STATUS_PENDING)
            ->latestOfMany();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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
