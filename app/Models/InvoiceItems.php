<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceItems extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'ticket_id',
        'description',
        'quantity',
        'unit_price',
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
     * Get the ticket associated with this invoice item.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}