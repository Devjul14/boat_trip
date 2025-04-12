<?php

namespace App\Models;

use App\Models\Trip;
use App\Models\Invoices;
use App\Models\TripPassengers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hotel extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoices::class);
    }

    /**
     * Get the trips that this hotel has participated in.
     */
    public function trips(): BelongsToMany
    {
        return $this->belongsToMany(Trip::class, 'trip_passengers')
            ->withPivot([
                'number_of_passengers',
                'excursion_charge',
                'boat_charge',
                'charter_charge',
                'total_usd',
                'total_rf',
                'payment_status',
                'payment_method'
            ])
            ->withTimestamps();
    }

    /**
     * Get all trip passengers records for this hotel.
     */
    public function tripPassengers(): HasMany
    {
        return $this->hasMany(TripPassengers::class);
    }
}
