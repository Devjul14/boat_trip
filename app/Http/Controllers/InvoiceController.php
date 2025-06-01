<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hotel;
use App\Models\Invoices;
use App\Models\Ticket;
use App\Models\TicketExpense;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function viewInvoiceSummary(Request $request, Invoices $invoice)
    {
        $trip = $invoice->trip;
        $hotel = $invoice->hotel;
    
        if (!$trip || !$hotel) {
            abort(404, 'Trip or Hotel not found');
        }
    
        $trip->load('tripType');
    
        $passengerCount = $trip->ticket()
                ->where('hotel_id', $hotel->id)
                ->sum('number_of_passengers');

        // Ambil total amount langsung dari invoice table
        $totalAmount = $invoice->total_amount;
        $totalPassengers = $passengerCount;
    
        $invoicesData = [[
            'invoice_number' => $invoice->invoice_number,
            'trip_date' => $trip->date,
            'trip_type' => $trip->tripType->name ?? 'N/A',
            'passenger_count' => $passengerCount,
            'month_year' => "{$invoice->month}/{$invoice->year}",
            'amount' => $totalAmount,
            'due_date' => $invoice->due_date
        ]];
    
        $expensesData = [];
        $invoiceItemsData = [];
    
        $tickets = Ticket::where('trip_id', $trip->id)
            ->where('hotel_id', $hotel->id)
            ->get();
    
        foreach ($tickets as $ticket) {
            $ticketExpenses = TicketExpense::with('expense')
                ->where('ticket_id', $ticket->id)
                ->get();
    
            foreach ($ticketExpenses as $expense) {
                $expensesData[] = [
                    'trip_date' => $trip->date,
                    'trip_type' => $trip->tripType->name ?? 'N/A',
                    'expense_type' => $expense->expense->name,
                    'passenger_count' => $ticket->number_of_passengers,
                    'amount' => $expense->amount, // Gunakan amount dari expense, bukan total amount
                    'notes' => $trip->notes,
                ];
            }
    
            $invoiceItemsData[] = [
                'ticket_id' => $ticket->id,
                'passenger_count' => $ticket->number_of_passengers,
                'amount' => $totalAmount, // Total amount dari invoice
            ];
        }
    
        // Generate PDF
        $pdf = PDF::loadView('pdfs.invoice-summary', [
            'hotel' => $hotel,
            'invoice' => $invoice,
            'trip' => $trip,
            'generatedDate' => now()->format('d-m-Y'),
            'invoicesData' => $invoicesData,
            'invoiceItemsData' => $invoiceItemsData,
            'expensesData' => $expensesData,
            'totalAmount' => $totalAmount, // Total amount dari invoice table
            'totalPassengers' => $totalPassengers,
        ])->setPaper('a4', 'portrait');
    
        // ?download=true
        $cleanInvoiceNumber = preg_replace('/[\/\\\\]/', '-', $invoice->invoice_number);

        if ($request->query('download') === 'true') {
            return $pdf->download("Invoice-{$cleanInvoiceNumber}.pdf");
        }

        return $pdf->stream("Invoice-{$cleanInvoiceNumber}.pdf");
    }
}