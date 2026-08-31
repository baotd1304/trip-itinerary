<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripImage extends Model
{
    protected $fillable = [
        'trip_id', 'public_id', 'url', 'format',
        'width', 'height', 'bytes', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'integer', 'height' => 'integer',
            'bytes' => 'integer', 'sort_order' => 'integer',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}