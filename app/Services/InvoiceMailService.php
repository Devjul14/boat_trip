<?php

namespace App\Services;

use App\Models\Hotel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InvoiceMailService
{
    public function send(Hotel $hotel, Collection $invoices): bool
    {
        try {
            Log::info("Starting invoice email send process. Hotel ID: {$hotel->id}, Invoice count: {$invoices->count()}");

            $firstInvoice = $invoices->first();
            if (!$firstInvoice) {
                throw new \Exception("No invoices available to send.");
            }

            Log::info("Using invoice ID: {$firstInvoice->id} to send email.");

            // Buat link ke route invoice view
            $viewLink = route('invoices.view', ['invoice' => $firstInvoice->id]);
            Log::info("Generated invoice view link: {$viewLink}");

            $data = [
                'hotel' => $hotel,
                'invoiceCount' => $invoices->count(),
                'totalAmount' => $invoices->sum('total_amount'),
                'contactPerson' => $hotel->contact_person,
                'month' => $firstInvoice->month,
                'year' => $firstInvoice->year,
                'viewLink' => $viewLink,
                'invoices' => $invoices->map(function ($invoice) {
                    return [
                        'invoice_number' => $invoice->invoice_number,
                        'total_amount' => $invoice->total_amount,
                        'due_date' => $invoice->due_date,
                        'trip_date' => $invoice->trip->date ?? null,
                    ];
                })->toArray()
            ];

            Log::info("Sending email to {$hotel->email} with invoice link.");

            Mail::send('emails.invoice-link', $data, function ($message) use ($hotel, $data) {
                $message->to($hotel->email, $hotel->contact_person)
                    ->subject("Invoice Summary for {$data['month']} {$data['year']}");
            });

            // Update status
            foreach ($invoices as $invoice) {
                $invoice->update(['status' => 'sent']);
                Log::info("Invoice ID {$invoice->id} marked as sent.");
            }

            Log::info("All invoices updated and email sent successfully to {$hotel->email}.");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to send invoice email: " . $e->getMessage());
            return false;
        }
    }
}
