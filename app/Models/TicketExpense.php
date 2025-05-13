<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketExpense extends Model
{
    protected $table = 'expenses_tickets';

    protected $fillable = [
        'expense_id',
        'ticket_id',
        'amount'
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}

