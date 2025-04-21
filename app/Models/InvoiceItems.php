<?php

namespace App\Models;

use App\Models\Trip;
use App\Models\Hotel;
use App\Models\Invoices;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceItems extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'trip_id',
        'description',
        'number_of_passengers',
        'excursion_charge',
        'boat_charge',
        'charter_charge',
        'total_amount',
    ];
    

    /**
     * Get the invoice that owns the invoice item.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoices::class);
    }

    /**
     * Get the trip associated with this invoice item.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
