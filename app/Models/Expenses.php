<?php

namespace App\Models;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expenses extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'expense_type',
        'amount',
        'notes',
    ];
    
    /**
     * Get the trip that owns the expense.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class, 'id');
    }
}
