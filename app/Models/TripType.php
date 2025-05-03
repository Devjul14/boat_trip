<?php

namespace App\Models;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TripType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'active',
        'image',
    ];
   protected $casts = [
        'active' => 'boolean',
    ];

   public function expenseTypes(): BelongsToMany
{
    return $this->belongsToMany(ExpenseType::class, 'expense_type_trip_types')
        ->withPivot('default_charge', 'is_master', 'trip_id')
        ->withTimestamps();
}

    /**
     * Get only the master default expense types for this trip type.
     */
    public function masterExpenseTypes()
{
    return $this->belongsToMany(ExpenseType::class, 'expense_type_trip_types')
                ->withPivot('default_charge', 'is_master', 'trip_id')
            ->withTimestamps();
}

  

    /**
     * Get the default charge for a specific expense type.
     */
    public function getDefaultChargeFor(int $expenseTypeId): float
    {
        $relation = $this->masterExpenseTypes()
            ->wherePivot('expense_type_id', $expenseTypeId)
            ->first();

        if (!$relation) {
            return 0;
        }

        return $relation->pivot->default_charge;
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? "trip-types/{$value}" : null,
        );
    }
    
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
    

    
}
