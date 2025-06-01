<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Hotel, Invoices};
use Illuminate\Http\Request;
use App\Services\InvoiceMailService;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    protected InvoiceMailService $invoiceMailService;

    public function __construct(InvoiceMailService $invoiceMailService)
    {
        $this->invoiceMailService = $invoiceMailService;
    }

    /**
     * View invoice PDF in browser
     */
    public function viewInvoice($invoiceId)
    {
        try {
            $invoice = Invoices::with(['hotel', 'trip.tripType', 'invoiceItems.ticket.ticketExpenses.expense'])
                ->findOrFail($invoiceId);

            $pdfData = $this->preparePdfData($invoice);

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.invoice-summary', $pdfData)
                ->setPaper('a4', 'portrait');

            return $pdf->stream("invoice-{$invoice->invoice_number}.pdf");

        } catch (\Exception $e) {
            Log::error("Error viewing invoice PDF: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate invoice PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send invoice email using link
     */
    public function sendInvoice(Request $request, $invoiceId)
    {
        Log::info("Triggered API sendInvoice() for invoice ID: {$invoiceId}");

        try {
            $invoice = Invoices::with(['hotel', 'trip.tripType', 'invoiceItems.ticket.ticketExpenses.expense'])
                ->findOrFail($invoiceId);

            $hotel = $invoice->hotel;
            if (!$hotel || !$hotel->email) {
                Log::warning("Hotel not found or missing email for invoice ID: {$invoiceId}");
                return response()->json([
                    'success' => false,
                    'message' => 'Hotel not found or missing email.'
                ], 400);
            }

            $trip = $invoice->trip;
            if (!$trip || $trip->status !== 'completed') {
                Log::warning("Trip not completed for invoice ID: {$invoiceId}");
                return response()->json([
                    'success' => false,
                    'message' => 'Trip not completed.'
                ], 400);
            }

            $paidCashPassengers = $trip->ticket()
                ->where('payment_status', 'paid')
                ->where('payment_method', 'cash')
                ->count();

            if ($paidCashPassengers > 0) {
                Log::warning("Trip has cash-paid passengers; aborting email.");
                return response()->json([
                    'success' => false,
                    'message' => 'Trip already paid in cash by some passengers.'
                ], 400);
            }

            $invoices = collect([$invoice]);
            $sent = $this->invoiceMailService->send($hotel, $invoices);

            return response()->json([
                'success' => $sent,
                'message' => $sent
                    ? "Invoice email sent successfully to {$hotel->email}."
                    : "Failed to send invoice email."
            ]);

        } catch (\Exception $e) {
            Log::error("Error in sendInvoice: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function preparePdfData(Invoices $invoice): array
    {
        $hotel = $invoice->hotel;
        $trip = $invoice->trip;

        $invoiceItemsData = [];
        $expensesData = [];
        $totalAmount = 0;

        foreach ($invoice->invoiceItems as $invoiceItem) {
            $ticket = $invoiceItem->ticket;
            $passengerCount = $ticket->number_of_passengers;
            $amount = $invoiceItem->unit_amount;
            $totalAmount += $amount;

            $invoiceItemsData[] = [
                'ticket_id' => $ticket->id,
                'trip_date' => $trip->date,
                'trip_type' => $trip->tripType->name ?? 'N/A',
                'passenger_count' => $passengerCount,
                'amount' => $amount,
                'expenses_breakdown' => $ticket->ticketExpenses->map(function ($expense) use ($passengerCount) {
                    return [
                        'expense_name' => $expense->expense->name,
                        'unit_amount' => $expense->amount,
                        'total_amount' => $expense->amount * $passengerCount,
                        'passenger_count' => $passengerCount
                    ];
                })->toArray()
            ];

            foreach ($ticket->ticketExpenses as $expense) {
                $expensesData[] = [
                    'trip_date' => $trip->date,
                    'trip_type' => $trip->tripType->name ?? 'N/A',
                    'expense_type' => $expense->expense->name,
                    'passenger_count' => $passengerCount,
                    'unit_amount' => $expense->amount,
                    'total_amount' => $expense->amount * $passengerCount,
                    'notes' => $trip->notes
                ];
            }
        }

        return [
            'hotel' => $hotel,
            'invoice' => $invoice,
            'trip' => $trip,
            'generatedDate' => now()->format('d-m-Y'),
            'invoiceItemsData' => $invoiceItemsData,
            'expensesData' => $expensesData,
            'totalAmount' => $totalAmount,
            'totalPassengers' => $invoice->invoiceItems->sum(function ($item) {
                return $item->ticket->number_of_passengers;
            })
        ];
    }
}
