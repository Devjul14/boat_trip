<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Trip, Ticket, TicketExpense, Hotel, Invoices, InvoiceItems};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Log, DB};
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class CompletedTripController extends Controller
{
    public function completeTrip(Request $request, $id)
    {
        try {
            Log::info("API: Completing trip ID: {$id}");
            $trip = Trip::findOrFail($id);

            if ($trip->status === 'completed') {
                return response()->json(['success' => false, 'message' => 'Trip is already completed'], 400);
            }

            if ($trip->status !== 'scheduled') {
                return response()->json(['success' => false, 'message' => 'Only scheduled trips can be completed'], 400);
            }

            DB::beginTransaction();
            $trip->update(['status' => 'completed']);

            $tickets = $trip->ticket()->get();
            $ticketIds = $tickets->pluck('id');
            Ticket::whereIn('id', $ticketIds)->update(['payment_status' => 'unpaid']);

            $totalExpenseAmount = TicketExpense::whereIn('ticket_id', $ticketIds)->sum('amount');
            $ticketsByHotel = $tickets->groupBy('hotel_id');
            $issueDate = $trip->date->format('Y-m-d');
            $dueDate = now()->addDays(7)->format('Y-m-d');

            $invoiceCount = 0;
            $resultInvoices = [];
            $pdfFiles = [];

            foreach ($ticketsByHotel as $hotelId => $hotelTickets) {
                if (!$hotelId) continue;

                $hotel = Hotel::find($hotelId);
                $hotelName = $hotel->name ?? 'Walk In Trip';
                $totalPassengers = $hotelTickets->sum('number_of_passengers');
                if ($totalPassengers === 0) continue;

                $totalInvoiceAmount = $totalExpenseAmount * $totalPassengers;
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
                        'unit_amount' => $totalExpenseAmount,
                    ]);
                }

                $invoiceCount++;
                $resultInvoices[] = [
                    'invoice_number' => $invoiceNumber,
                    'hotel_name' => $hotelName,
                    'total_passengers' => $totalPassengers,
                    'total_amount' => $totalInvoiceAmount,
                ];

                $invoicesForHotel = Invoices::where('hotel_id', $hotelId)->where('trip_id', $trip->id)->get();
                $pdfPath = self::generateInvoicePDF($hotel, $invoicesForHotel);
                $pdfFiles[] = ['hotel_name' => $hotelName, 'pdf_path' => $pdfPath];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Trip completed. Generated {$invoiceCount} invoice(s).",
                'data' => [
                    'trip_id' => $trip->id,
                    'date' => $trip->date->format('Y-m-d'),
                    'invoices' => $resultInvoices,
                    'pdf_files' => $pdfFiles
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error completing trip {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to complete trip: ' . $e->getMessage()], 500);
        }
    }

    protected static function generateInvoicePDF(Hotel $hotel, Collection $invoices): string
    {
        try {
            $invoicesData = [];
            $expensesData = [];
            $totalAmount = 0;

            foreach ($invoices as $invoice) {
                $trip = Trip::with('tripType')->find($invoice->trip_id);
                if (!$trip) continue;

                $passengerCount = $trip->ticket()->where('hotel_id', $hotel->id)->sum('number_of_passengers');
                $amount = $invoice->total_amount;
                $totalAmount += $amount;

                $invoicesData[] = [
                    'invoice_number' => $invoice->invoice_number,
                    'trip_date' => $trip->date,
                    'trip_type' => $trip->tripType->name ?? 'N/A',
                    'passenger_count' => $passengerCount,
                    'month_year' => "{$invoice->month}/{$invoice->year}",
                    'amount' => $amount,
                    'due_date' => $invoice->due_date
                ];

                $ticketIds = Ticket::where('trip_id', $trip->id)->where('hotel_id', $hotel->id)->pluck('id');
                $ticketExpenses = TicketExpense::with('expense')->whereIn('ticket_id', $ticketIds)->get();

                foreach ($ticketExpenses as $expense) {
                    $ticket = Ticket::find($expense->ticket_id);
                    $expensesData[] = [
                        'trip_date' => $trip->date,
                        'trip_type' => $trip->tripType->name ?? 'N/A',
                        'expense_type' => $expense->expense->name,
                        'passenger_count' => $ticket->number_of_passengers ?? 1,
                        'amount' => $expense->amount,
                        'notes' => $trip->notes
                    ];
                }
            }

            $pdf = PDF::loadView('pdfs.invoice-summary', [
                'hotel' => $hotel,
                'invoices' => $invoices,
                'generatedDate' => now()->format('d-m-Y'),
                'invoicesData' => $invoicesData,
                'expensesData' => $expensesData,
                'totalAmount' => $totalAmount,
            ])->setPaper('a4', 'portrait');

            $directory = storage_path('app/public/pdf');
            if (!file_exists($directory)) mkdir($directory, 0755, true);

            $fileName = Str::slug("{$hotel->name}" . date('YmdHis')) . '.pdf';
            $filePath = "{$directory}/{$fileName}";
            $pdf->save($filePath);

            if (!file_exists($filePath)) {
                throw new \Exception("Failed to create PDF at: {$filePath}");
            }

            return $filePath;
        } catch (\Exception $e) {
            Log::error("Error generating PDF: " . $e->getMessage());
            throw $e;
        }
    }
}
