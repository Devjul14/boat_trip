<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\TripCompletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CompletedTripController extends Controller
{
    protected $tripCompletionService;

    public function __construct(TripCompletionService $tripCompletionService)
    {
        $this->tripCompletionService = $tripCompletionService;
    }

    public function completeTrip(Request $request, $id)
    {
        Log::info("API: Starting completion for trip ID: {$id}");

        try {
            $trip = Trip::findOrFail($id);

            $result = $this->tripCompletionService->complete($trip);

            Log::info("API: Trip ID {$id} completed successfully");

            return response()->json([
                'success' => true,
                'message' => "Trip completed. Generated {$result['invoice_count']} invoice(s).",
                'data' => [
                    'trip_id' => $result['trip_id'],
                    'date' => $result['date'],
                    'invoices' => $result['invoices']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("API: Error completing trip {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete trip: ' . $e->getMessage()
            ], 500);
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
