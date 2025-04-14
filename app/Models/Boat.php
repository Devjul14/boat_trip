<?php

namespace App\Models;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Boat extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'capacity', 
        'registration_number',
        'user_id',
        'boatman_id',
    ];

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function boatman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'boatman_id');
    }
}
