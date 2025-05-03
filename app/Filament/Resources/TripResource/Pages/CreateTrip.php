<?php

namespace App\Filament\Resources\TripResource\Pages;

use App\Filament\Resources\TripResource;
use Filament\Actions;
use App\Models\TripType;
use App\Models\ExpenseTypeTripType;
use App\Models\ExpenseType;
use App\Models\Expenses;
use App\Models\Ticket;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateTrip extends CreateRecord
{
    protected static string $resource = TripResource::class;

    protected function afterCreate(): void
    {
        $tripId = $this->record->id;
        $tripTypeId = $this->record->trip_type_id;

        if (!$tripTypeId) {
            Log::warning('afterCreate aborted: Missing tripTypeId');
            return;
        }

        $expenseTypes = $this->form->getState()['expense_type'] ?? [];

        if (empty($expenseTypes)) {
            Log::warning('afterCreate aborted: No expense_type found');
            return;
        }

        $tripType = TripType::find($tripTypeId);
        if (!$tripType) {
            Log::error("TripType not found for ID: {$tripTypeId}");
            return;
        }

        // Retrieve tickets associated with the trip
        $tickets = Ticket::where('trip_id', $tripId)->get();

        if ($tickets->isEmpty()) {
            Log::warning("No tickets found for trip ID: {$tripId}");
            return;
        }

        \DB::transaction(function () use ($tripId, $tripType, $expenseTypes, $tickets,$tripTypeId) {
            foreach ($tickets as $ticket) {
                $ticketId = $ticket->id; // Get the ticket ID
                $numberOfPassengers = $ticket->number_of_passengers ?? 1; // Assuming this field exists

                foreach ($expenseTypes as $expenseId) {
                    $expenseType = ExpenseType::find($expenseId);
                    if (!$expenseType) {
                        Log::error("ExpenseType not found for ID: {$expenseId}");
                        continue;
                    }

                    // Get the default charge from the pivot table
                    $expenseTypeTripType = ExpenseTypeTripType::where('trip_type_id', $tripTypeId)
                        ->where('expense_type_id', $expenseId)
                        ->first();

                    if (!$expenseTypeTripType) {
                        Log::error("No default charge found for TripType ID: {$tripTypeId} and ExpenseType ID: {$expenseId}");
                        continue;
                    }

                    $charge = $expenseTypeTripType->default_charge; // Get the default charge


                    // Generate notes
                    $notes = "Trip Type: {$tripType->name}, Expense Type: {$expenseType->name}, Charge: {$charge}";

                    // Create the expense record
                    Expenses::create([
                        'trip_id' => $tripId,
                        'ticket_id' => $ticketId, 
                        'expense_type' => $expenseId,
                        'amount' => $charge,
                        'notes' => $notes,
                    ]);

                    // Initialize total_usd if null and add charge
                    if (is_null($ticket->total_usd)) {
                        $ticket->total_usd = 0;
                    }
                    $ticket->total_usd += $numberOfPassengers * $charge;
                }

                // Save ticket total
                $ticket->save();
            }
        });

        Log::info("afterCreate finished processing expenses and tickets for trip ID {$tripId}.");
    }

    
}