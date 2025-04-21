<?php

namespace App\Models;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TripType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'default_excursion_charge',
        'default_boat_charge',
        'default_charter_charge',
    ];

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
