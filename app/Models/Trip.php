<?php

namespace App\Models;

use App\Models\Boat;
use App\Models\User;
use App\Models\Hotel;
use App\Models\Expenses;
use App\Models\TripType;
use App\Models\InvoiceItems;
use App\Models\TripPassengers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Trip extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function tripType(): BelongsTo
    {
        return $this->belongsTo(TripType::class);
    }

    /**
     * Get the boat for this trip.
     */
    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }

    /**
     * Get the boatman (user) for this trip.
     */
    public function boatman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'boatman_id');
    }

    /**
     * Get the expenses for this trip.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expenses::class);
    }

    /**
     * Get all passenger records for this trip as ticket.
     */
    public function tripPassengers(): HasMany
    {
        return $this->hasMany(TripPassengers::class);
    }

    /**
     * Get the invoice items associated with this trip.
     */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItems::class);
    }
}
