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

    protected $fillable = [
        'trip_id',
        'hotel_id',
        'number_of_passengers',
        'excursion_charge',
        'boat_charge',
        'charter_charge',
        'total_usd',
        'total_rf',
        'payment_status',
        'payment_method',
    ];

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
