<?php

namespace App\Services;

use App\Models\{Trip, Ticket, TicketExpense, Hotel, Invoices, InvoiceItems};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class TripCompletionService
{
    public function complete(Trip $trip): array
    {
        if ($trip->status === 'completed') {
            throw new \Exception("Trip is already completed");
        }

        if ($trip->status !== 'scheduled') {
            throw new \Exception("Only scheduled trips can be completed");
        }

        DB::beginTransaction();

        try {
            Log::info("Completing trip ID: {$trip->id}");

            // Update trip status
            $trip->update(['status' => 'completed']);
            Log::info("Trip status updated to completed");

            // Get tickets with their expenses - eager load for performance
            $tickets = $trip->ticket()->with(['ticketExpenses.expense', 'hotel'])->get();
            
            if ($tickets->isEmpty()) {
                throw new \Exception("No tickets found for this trip");
            }

            $ticketIds = $tickets->pluck('id');

            // Update all tickets to unpaid status
            Ticket::whereIn('id', $ticketIds)->update(['payment_status' => 'unpaid']);
            Log::info("Updated payment status to unpaid for tickets: " . $ticketIds->implode(','));

            // Group tickets by hotel (including null for walk-in tickets)
            $ticketsByHotel = $tickets->groupBy('hotel_id');
            
            $invoiceCount = 0;
            $resultInvoices = [];

            // Get last invoice number safely
            $lastInvoice = Invoices::orderByDesc('id')->first();
            $lastNumber = 0;
            if ($lastInvoice && preg_match('/AK\/\d{4}\/(\d+)/', $lastInvoice->invoice_number, $matches)) {
                $lastNumber = (int) $matches[1];
            }
            $currentInvoiceNumber = $lastNumber;

            $issueDate = $trip->date;
            $dueDate = now()->addDays(7)->format('Y-m-d');

            foreach ($ticketsByHotel as $hotelId => $hotelTickets) {
                // Skip if no hotel_id (unless you want to handle walk-ins)
                if (!$hotelId) {
                    Log::info("Skipping tickets without hotel_id (walk-in tickets)");
                    continue;
                }

                $hotel = Hotel::find($hotelId);
                if (!$hotel) {
                    Log::warning("Hotel not found for ID: {$hotelId}");
                    continue;
                }

                // Calculate total invoice amount for this hotel
                $totalInvoiceAmount = 0;
                $invoiceItems = [];

                foreach ($hotelTickets as $ticket) {
                    // Calculate ticket total: sum of (expense_amount * number_of_passengers)
                    $ticketTotal = $ticket->ticketExpenses->sum(function ($ticketExpense) use ($ticket) {
                        return $ticketExpense->amount * $ticket->number_of_passengers;
                    });

                    $totalInvoiceAmount += $ticketTotal;
                    
                    $invoiceItems[] = [
                        'ticket_id' => $ticket->id,
                        'unit_amount' => $ticketTotal,
                        'passengers' => $ticket->number_of_passengers,
                        'expenses' => $ticket->ticketExpenses->map(function ($exp) {
                            return [
                                'name' => $exp->expense->name,
                                'amount' => $exp->amount
                            ];
                        })->toArray()
                    ];

                    Log::info("Ticket ID {$ticket->id}: passengers={$ticket->number_of_passengers}, total={$ticketTotal}");
                }

                // Generate invoice number
                $currentInvoiceNumber++;
                $invoiceNumber = 'AK/' . date('Y') . '/' . str_pad($currentInvoiceNumber, 3, '0', STR_PAD_LEFT);

                Log::info("Creating invoice {$invoiceNumber} for hotel {$hotel->name} with amount {$totalInvoiceAmount}");

                // Create the invoice
                $invoice = Invoices::create([
                    'invoice_number' => $invoiceNumber,
                    'hotel_id' => $hotelId,
                    'trip_id' => $trip->id,
                    'month' => date('F'),
                    'year' => date('Y'),
                    'issue_date' => $issueDate,
                    'due_date' => $dueDate,
                    'total_amount' => $totalInvoiceAmount,
                    'status' => 'draft',
                ]);

                // Create invoice items for each ticket
                foreach ($invoiceItems as $item) {
                    InvoiceItems::create([
                        'invoice_id' => $invoice->id,
                        'ticket_id' => $item['ticket_id'],
                        'unit_amount' => $item['unit_amount'],
                    ]);
                }

                $invoiceCount++;
                $resultInvoices[] = [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoiceNumber,
                    'hotel_name' => $hotel->name,
                    'total_passengers' => $hotelTickets->sum('number_of_passengers'),
                    'total_amount' => $totalInvoiceAmount,
                    'ticket_count' => $hotelTickets->count(),
                ];
            }

            DB::commit();
            Log::info("Trip completion finished successfully. Created {$invoiceCount} invoices.");

            return [
                'trip_id' => $trip->id,
                'date' => $trip->date->format('Y-m-d'),
                'invoice_count' => $invoiceCount,
                'invoices' => $resultInvoices
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Trip completion failed: " . $e->getMessage());
            throw $e;
        }
    }
}