<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseTypeTripType extends Model
{
    protected $table = 'expense_type_trip_types';
    
    protected $fillable = [
        'trip_type_id',
        'expense_type_id',
        'trip_id',
        'default_charge',
        'is_master',
    ];

    protected $casts = [
        'default_charge' => 'decimal:2',
        'is_master' => 'boolean',
    ];

    /**
     * Get the trip type that this relationship belongs to.
     */
    public function tripType(): BelongsTo
    {
        return $this->belongsTo(TripType::class);
    }

    /**
     * Get the expense type that this relationship belongs to.
     */
    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class);
    }

    /**
     * Get the trip that this relationship belongs to (if any).
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Scope a query to only include master default relationships.
     */
    public function scopeMaster($query)
    {
        return $query->where('is_master', true);
    }

    /**
     * Scope a query to only include relationships for a specific trip.
     */
    public function scopeForTrip($query, $tripId)
    {
        return $query->where('trip_id', $tripId);
    }

    /**
     * Get the default charge for a specific trip type and expense type.
     * Prioritizes trip-specific charges over master defaults.
     */
    public static function getCharge(int $tripTypeId, int $expenseTypeId, ?int $tripId = null): float
    {
        // First try to find a trip-specific charge if tripId is provided
        if ($tripId) {
            $tripSpecific = self::where('trip_type_id', $tripTypeId)
                ->where('expense_type_id', $expenseTypeId)
                ->where('trip_id', $tripId)
                ->first();

            if ($tripSpecific) {
                return $tripSpecific->default_charge;
            }
        }

        // If no trip-specific charge, look for the master default
        $master = self::where('trip_type_id', $tripTypeId)
            ->where('expense_type_id', $expenseTypeId)
            ->where('is_master', true)
            ->first();

        if ($master) {
            return $master->default_charge;
        }

        return 0; // Default if no charge is found
    }
}
