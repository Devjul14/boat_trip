<?php

namespace App\Models;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Boat extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
