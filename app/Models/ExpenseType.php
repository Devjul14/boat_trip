<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'code',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get the trip types that this expense type belongs to.
     */
    public function tripTypes(): BelongsToMany
{
    return $this->belongsToMany(TripType::class, 'expense_type_trip_types')
        ->withPivot('default_charge', 'is_master', 'trip_id')
        ->withTimestamps();
}

    /**
     * Get only the master default trip types for this expense type.
     */
    public function masterTripTypes(): BelongsToMany
    {
        return $this->belongsToMany(TripType::class, 'expense_type_trip_types')
            ->withPivot('default_charge', 'is_master', 'trip_id')
            ->wherePivot('is_master', true)
            ->wherePivot('trip_id', null)
            ->withTimestamps();
    }

    /**
     * Get expenses of this type.
     */
    public function expenses()
    {
        return $this->hasMany(Expenses::class, 'expense_type', 'id');
    }

    /**
     * Get the default charge for a specific trip type.
     */
    public function getDefaultChargeFor(int $tripTypeId): float
    {
        $relation = $this->masterTripTypes()
            ->wherePivot('trip_type_id', $tripTypeId)
            ->first();

        if (!$relation) {
            return 0;
        }

        return $relation->pivot->default_charge;
    }
}
