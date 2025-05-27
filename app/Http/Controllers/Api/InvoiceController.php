<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Invoices;
use App\Models\Trip;
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

    public function sendInvoice(Request $request, $invoiceId)
    {
        Log::info("Triggered API sendInvoice() for invoice ID: {$invoiceId}");

        $invoice = Invoices::find($invoiceId);
        if (!$invoice) {
            Log::warning("Invoice not found with ID: {$invoiceId}");
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found.'
            ], 404);
        }

        $hotel = Hotel::find($invoice->hotel_id);
        if (!$hotel || !$hotel->email) {
            Log::warning("Hotel not found or missing email for invoice ID: {$invoiceId}");
            return response()->json([
                'success' => false,
                'message' => 'Hotel not found or missing email.'
            ], 400);
        }

        $trip = Trip::find($invoice->trip_id);
        if (!$trip || $trip->status !== 'completed') {
            Log::warning("Trip not found or not completed for invoice ID: {$invoiceId}");
            return response()->json([
                'success' => false,
                'message' => 'Trip not found or not completed.'
            ], 400);
        }

        $paidCashPassengers = $trip->ticket()
            ->where('payment_status', 'paid')
            ->where('payment_method', 'cash')
            ->count();
        Log::info("Found {$paidCashPassengers} cash-paid passengers for trip ID: {$trip->id}");

        if ($paidCashPassengers > 0) {
            Log::warning("Trip has cash-paid passengers; aborting email for invoice ID: {$invoiceId}");
            return response()->json([
                'success' => false,
                'message' => 'Trip has already been paid in cash by some passengers.'
            ], 400);
        }

        Log::info("Preparing to send invoice email to {$hotel->email} for invoice ID: {$invoice->id}");

        $invoices = collect([$invoice]);

        $sent = $this->invoiceMailService->send($hotel, $invoices);

        if ($sent) {
            Log::info("Invoice email sent successfully to {$hotel->email}");
        } else {
            Log::error("Failed to send invoice email to {$hotel->email}");
        }

        return response()->json([
            'success' => $sent,
            'message' => $sent
                ? "Invoice email sent successfully to {$hotel->email}."
                : "Failed to send invoice email."
        ]);
    }


}
