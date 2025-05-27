<?php

namespace App\Services;

use App\Models\Hotel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvoiceMailService
{
    public function send(Hotel $hotel, Collection $invoices): bool
{
    try {
        \Log::info("Starting invoice email send process. Hotel ID: {$hotel->id}, Invoice count: {$invoices->count()}");

        $firstInvoice = $invoices->first();
        if (!$firstInvoice) {
            throw new \Exception("No invoices available to send.");
        }

        \Log::info("Using invoice ID: {$firstInvoice->id} to generate file name.");

        $timestamp = date('YmdHis', strtotime($firstInvoice->created_at));
        $baseFileName = "{$hotel->name}{$timestamp}";
        $fileName = Str::slug($baseFileName) . '.pdf';

        $pdfFilePath = storage_path("app/public/pdf/{$fileName}");
        \Log::info("Expected PDF path: {$pdfFilePath}");

        if (!file_exists($pdfFilePath)) {
            throw new \Exception("PDF file not found at path: {$pdfFilePath}");
        }

        $fileSize = filesize($pdfFilePath);
        \Log::info("PDF file found. Size: {$fileSize} bytes");

        $data = [
            'hotel' => $hotel,
            'invoiceCount' => $invoices->count(),
            'totalAmount' => $invoices->sum('total_amount'),
            'contactPerson' => $hotel->contact_person,
            'month' => $firstInvoice->month,
            'year' => $firstInvoice->year,
        ];

        \Log::info("Sending email to {$hotel->email} with data: ", $data);

        Mail::send('emails.invoice-pdf', $data, function ($message) use ($hotel, $pdfFilePath, $data, $fileName) {
            $message->to($hotel->email, $hotel->contact_person)
                ->subject("Invoice Summary for {$data['month']} {$data['year']}")
                ->attach($pdfFilePath, [
                    'as' => $fileName,
                    'mime' => 'application/pdf',
                ]);
        });

        \Log::info("Email dispatched. Updating invoice statuses...");

        foreach ($invoices as $invoice) {
            $invoice->update(['status' => 'sent']);
            \Log::info("Invoice ID {$invoice->id} marked as sent.");
        }

        \Log::info("All invoices updated and email sent successfully to {$hotel->email}.");
        return true;
    } catch (\Exception $e) {
        \Log::error("Failed to send invoice PDF email: " . $e->getMessage());
        return false;
    }
}

}
