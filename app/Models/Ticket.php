<?php

namespace App\Models;

use App\Models\Trip;
use App\Models\Hotel;
use App\Models\Invoices;
use App\Models\TripPassengers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'trip_id',
        'hotel_id',
        'is_hotel_ticket',
        'number_of_passengers',
        'price',
        'total_usd',
        'total_rf',
        'payment_method',
        'payment_status',
    ];
    
    // Add the calculated field to the appends array
    protected $appends = ['total_amount'];

    // Auto-load relationships by default
    protected $with = ['ticketExpenses'];
    
    // Calculated attribute for total amount
    public function getTotalAmountAttribute()
    {
        return $this->ticketExpenses->sum(function ($expense) {
            return $expense->amount * $this->number_of_passengers;
        });
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

    public function ticketExpenses()
    {
        return $this->hasMany(TicketExpense::class);
    }

    
}
