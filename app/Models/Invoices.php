<?php

namespace App\Models;

use App\Models\Trip;
use App\Models\Hotel;
use App\Models\InvoiceItems;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoices extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'hotel_id',
        'trip_id',
        'month',
        'year',
        'issue_date',
        'due_date',
        'total_amount',
        'status',
    ];

    /**
     * Get the hotel that owns the invoice.
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

        /**
     * Get the trip associated with this invoice.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
