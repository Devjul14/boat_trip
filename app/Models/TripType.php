<?php

namespace App\Models;

use App\Models\Trip;
use App\Models\Expense;
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

   public function expenses()
    {
        return $this->belongsToMany(Expense::class, 'expense_trip_types', 'trip_type_id', 'expense_id');
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
