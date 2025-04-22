<?php

namespace App\Models;

use App\Models\Trip;
use App\Models\Hotel;
use App\Models\Invoices;
use App\Models\TripPassengers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'trip_id',
        'passenger_id',
        'is_hotel_ticket',
    ];

    public function tripPassenger(): BelongsTo
    {
        return $this->belongsTo(TripPassengers::class, 'passenger_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoices::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    
}
