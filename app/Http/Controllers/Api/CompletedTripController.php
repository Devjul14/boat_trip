<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Hotel;
use App\Models\Invoices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CompletedTripController extends Controller
{
    public function completeTrip(Request $request, $id)
    {
        try {
            Log::info("API: Starting trip completion process for trip ID: {$id}");
            
            // Find the trip by ID
            $trip = Trip::findOrFail($id);
            
            // Check if trip is already completed
            if ($trip->status === 'completed') {
                Log::warning("API: Trip {$id} is already completed");
                return response()->json([
                    'success' => false,
                    'message' => 'Trip is already completed',
                ], 400);
            }
            
            // Check if trip is in scheduled status
            if ($trip->status !== 'scheduled') {
                Log::warning("API: Trip {$id} has status '{$trip->status}', not 'scheduled'");
                return response()->json([
                    'success' => false,
                    'message' => 'Only scheduled trips can be completed',
                ], 400);
            }
            
            // Update trip status
            $trip->update(['status' => 'completed']);
            Log::info("API: Trip status updated to 'completed'");
            
            // Generate invoices for each hotel in the trip
            $ticketsByHotel = $trip->ticket()->where('is_hotel_ticket', true)->get()->groupBy('hotel_id');
            Log::info("API: Found " . count($ticketsByHotel) . " hotels with tickets");
            
            $invoiceCount = 0;
            $ticketCount = 0;
            
            // Calculate issue date from trip date
            $issueDate = $trip->date;
            $dueDate = date('Y-m-d', strtotime($issueDate . ' + 7 days'));
            Log::info("API: Issue date: {$issueDate}, Due date: {$dueDate}");
            
            // Get all expenses for this trip
            $tripExpenses = $trip->expenses()->get();
            $totalExpenseAmount = $tripExpenses->sum('amount');
            Log::info("API: Total expense amount for trip: {$totalExpenseAmount}");
            
            $invoices = [];
            
            foreach ($ticketsByHotel as $hotelId => $tickets) {
                if (!$hotelId) {
                    Log::info("API: Skipping hotel with null ID");
                    continue;
                }
                
                $hotelName = Hotel::find($hotelId)->name ?? "Walk In Trip";
                Log::info("API: Processing hotel ID: {$hotelId} ({$hotelName})");

                // Get total passengers for this hotel
                $totalPassengers = $tickets->sum('number_of_passengers');
                Log::info("API: Total passengers for hotel {$hotelId}: {$totalPassengers}");
                
                // Calculate expense portion for this hotel (expense amount * passenger count)
                $totalInvoice = $totalExpenseAmount * $totalPassengers;
                Log::info("API: Expense amount for hotel {$hotelId}: {$totalInvoice} (calculated as {$totalExpenseAmount} * {$totalPassengers})");
                
                // Generate invoice number
                $lastInvoice = Invoices::orderBy('id', 'desc')->first();
                $lastNumber = $lastInvoice ? intval(substr($lastInvoice->invoice_number, 8, 3)) : 0;
                $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
                $invoiceNumber = 'AK/' . date('Y') . '/' . $newNumber;
                Log::info("API: Generated invoice number: {$invoiceNumber}");
            
                // Create invoice record with trip_id, issue_date, and due_date
                $invoice = Invoices::create([
                    'invoice_number' => $invoiceNumber,
                    'hotel_id' => $hotelId,
                    'trip_id' => $trip->id,
                    'month' => date('F'),
                    'year' => date('Y'),
                    'issue_date' => $issueDate,
                    'due_date' => $dueDate,
                    'total_amount' => $totalInvoice,
                    'status' => 'draft',
                ]);
                Log::info("API: Created invoice ID: {$invoice->id} for hotel {$hotelId}");

                $invoiceCount++;
                $invoices[] = $invoice->invoice_number;
            }
            
            Log::info("API: Trip completion process finished. Generated {$invoiceCount} invoices");
            
            return response()->json([
                'success' => true,
                'message' => "Trip completed successfully. Generated {$invoiceCount} invoice(s).",
                'data' => [
                    'trip_id' => $trip->id,
                    'date' => $trip->date,
                    'invoices' => $invoices
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error("API: Trip completion error for trip {$id}: " . $e->getMessage());
            Log::error("API: Exception stack trace: " . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete trip: ' . $e->getMessage()
            ], 500);
        }
    }
}