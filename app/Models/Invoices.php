<?php

namespace App\Models;

use App\Models\InvoiceItems;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoices extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Get the hotel that owns the invoice.
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Get the invoice items for this invoice.
     */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItems::class);
    }
}
