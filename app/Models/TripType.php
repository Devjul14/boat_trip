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
            ->withPivot('default_charge', 'is_master')
            ->wherePivot('is_master', true)
            ->wherePivotNull('trip_id');
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

  

    public function defaultExpenseTypes(): BelongsToMany
    {
        return $this->belongsToMany(ExpenseType::class, 'expense_type_trip_types')
            ->withPivot(['default_charge'])
            ->wherePivot('is_master', true)
            ->wherePivot('trip_id', null)
            ->withTimestamps();
    }

    /**
     * Save default charges for expense types
     * 
     * @param array $charges Key-value pairs of expense_type_id => charge_amount
     * @return void
     */
    public function saveDefaultCharges(array $charges): void
    {
        DB::beginTransaction();
        
        try {
            foreach ($charges as $expenseTypeId => $charge) {
                $normalizedCharge = ($charge === null || $charge === '') ? 0.00 : (float) $charge;
                
                DB::table('expense_type_trip_types')->updateOrInsert(
                    [
                        'trip_type_id' => $this->id,
                        'expense_type_id' => $expenseTypeId,
                        'is_master' => true,
                        'trip_id' => null,
                    ],
                    [
                        'default_charge' => $normalizedCharge,
                        'updated_at' => now(),
                        'created_at' => DB::raw('IFNULL(created_at, NOW())'),
                    ]
                );
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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
