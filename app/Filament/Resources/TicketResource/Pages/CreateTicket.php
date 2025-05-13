<?php 

// File: app/Filament/Resources/TicketResource/Pages/CreateTicket.php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Trip;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Model;
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

    // ✅ Tambahkan ini agar trip_id ikut tersimpan di form
    $this->form->fill([
        'trip_id' => $tripId,
        'expense_amounts' => $expenseAmounts,
    ]);

    $this->trip_id = $tripId; // Optional, untuk Livewire binding juga

    Log::debug('FILLING FORM:', [
        'trip_id' => $tripId,
        'expense_amounts' => $expenseAmounts,
    ]);
}


    

protected function handleRecordCreation(array $data): Model
{
    // Ambil data expense_amounts dan pisahkan dari data utama
    $expenseAmounts = $data['expense_amounts'] ?? [];
    unset($data['expense_amounts']);

    // Buat record Ticket
    $ticket = Ticket::create($data);

    // Loop dan simpan setiap expense terkait
    foreach ($expenseAmounts as $expenseData) {
        if (!isset($expenseData['expense_id'], $expenseData['amount'])) {
            continue; // Lewati jika datanya tidak lengkap
        }

        $ticket->ticketExpenses()->create([
            'expense_id' => $expenseData['expense_id'],
            'amount' => $expenseData['amount'],
        ]);
    }

    Log::info('success create ticketExpenses');
    return $ticket;
}


}
