<?php

namespace App\Models;

use App\Models\Trip;
use App\Models\Hotel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TripPassengers extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Get the trip that owns the passenger record.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the hotel that owns the passenger record.
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}
