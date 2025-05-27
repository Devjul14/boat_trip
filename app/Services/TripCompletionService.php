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
            $trip->update(['status' => 'completed']);

            $tickets = $trip->ticket()->get();
            $ticketIds = $tickets->pluck('id');
            Ticket::whereIn('id', $ticketIds)->update(['payment_status' => 'unpaid']);

            $ticketExpenses = TicketExpense::whereIn('ticket_id', $ticketIds)->get();
            $totalExpenseAmount = $ticketExpenses->sum('amount');

            $ticketsByHotel = $tickets->groupBy('hotel_id');
            $issueDate = $trip->date;
            $dueDate = now()->addDays(7)->format('Y-m-d');

            $invoiceCount = 0;
            $pdfFiles = [];
            $resultInvoices = [];

            foreach ($ticketsByHotel as $hotelId => $hotelTickets) {
                if (!$hotelId) continue;

                $hotel = Hotel::find($hotelId);
                $hotelName = $hotel->name ?? 'Walk In Trip';
                $totalPassengers = $hotelTickets->sum('number_of_passengers');

                if ($totalPassengers === 0) continue;

                $totalInvoiceAmount = $totalExpenseAmount * $totalPassengers;
                $unitAmount = $totalExpenseAmount;

                $lastNumber = (int) (Invoices::latest()->first()?->invoice_number ? substr(Invoices::latest()->first()->invoice_number, 8, 3) : 0);
                $invoiceNumber = 'AK/' . date('Y') . '/' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

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

                foreach ($hotelTickets as $ticket) {
                    InvoiceItems::create([
                        'invoice_id' => $invoice->id,
                        'ticket_id' => $ticket->id,
                        'unit_amount' => $unitAmount,
                    ]);
                }

                $invoicesForHotel = Invoices::where('hotel_id', $hotelId)->where('trip_id', $trip->id)->get();
                $pdfPath = \App\Http\Controllers\Api\CompletedTripController::generateInvoicePDF($hotel, $invoicesForHotel);
                $pdfFiles[] = ['hotel_name' => $hotelName, 'pdf_path' => $pdfPath];

                $invoiceCount++;
                $resultInvoices[] = [
                    'invoice_number' => $invoiceNumber,
                    'hotel_name' => $hotelName,
                    'total_passengers' => $totalPassengers,
                    'total_amount' => $totalInvoiceAmount,
                ];
            }

            DB::commit();

            return [
                'trip_id' => $trip->id,
                'date' => $trip->date->format('Y-m-d'),
                'invoice_count' => $invoiceCount,
                'invoices' => $resultInvoices,
                'pdf_files' => $pdfFiles
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Trip completion failed: " . $e->getMessage());
            throw $e;
        }
    }
}
