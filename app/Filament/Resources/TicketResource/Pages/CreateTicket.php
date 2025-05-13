<?php 

// File: app/Filament/Resources/TicketResource/Pages/CreateTicket.php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Trip;
use Illuminate\Support\Facades\Log;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    public $trip_id;
    public $expense_amounts = [];

    public function updatedTripId($tripId)
    {
        $this->loadExpenses($tripId);
    }

    public function loadExpenses($tripId)
    {
        $trip = Trip::with('tripType.expenses')->findOrFail($tripId);

        if (!$trip->tripType) return;

        $expenseAmounts = $trip->tripType->expenses->map(function ($expense) {
            return [
                'expense_id' => $expense->id,
                'expense_name' => $expense->name,
                'amount' => 0,
            ];
        })->toArray();

        // Setel ke form state, bukan hanya ke property
        $this->form->fill([
            'expense_amounts' => $expenseAmounts,
        ]);
        
        Log::debug('FILLING FORM:', [
            'expense_amounts' => $expenseAmounts,
        ]);
    }

}
