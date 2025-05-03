<?php

namespace App\Models;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Expenses extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'ticket_id',
        'expense_type',
        'amount',
        'notes',
    ];

    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class,'expense_type');
    }
    
    /**
     * Get the trip that owns the expense.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the tickets associated with the expense.
     * Many-to-many relationship
     */
    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'expense_ticket', 'expense_id', 'ticket_id')
                    ->withTimestamps();
    }

    
}
