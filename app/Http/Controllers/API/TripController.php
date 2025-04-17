<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Hotel;
use App\Models\Invoices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TripController extends Controller
{
    /**
     * Complete a trip via API
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function completeTrip(Request $request, $id)
    {
        try {
            Log::info("API: Starting trip completion process for trip ID: {$id}");
            
            // Find the trip by ID
            $trip = Trip::findOrFail($id);
            Log::info("API: Found trip with bill number: {$trip->bill_number}");
            
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
            Log::info("API: Updated trip {$id} status to 'completed'");
            
            // Generate invoices for each hotel in the trip
            $tripPassengersByHotel = $trip->tripPassengers()->get()->groupBy('hotel_id');
            Log::info("API: Found " . count($tripPassengersByHotel) . " hotels for trip {$id}");
            
            $invoices = [];
            
            foreach ($tripPassengersByHotel as $hotelId => $passengers) {
                if (!$hotelId) {
                    Log::info("API: Skipping null hotel_id for trip {$id}");
                    continue; // Skip if hotel_id is null
                }
                
                Log::info("API: Processing hotel ID: {$hotelId} with " . count($passengers) . " passenger records");
                
                // Calculate total amount for this hotel
                $totalAmount = $passengers->sum(function ($passenger) {
                    $passengerCount = $passenger->number_of_passengers;
                    $perPassengerCharge = ($passenger->excursion_charge + $passenger->boat_charge);
                    return ($perPassengerCharge * $passengerCount) + $passenger->charter_charge;
                });
                
                Log::info("API: Calculated total amount for hotel {$hotelId}: {$totalAmount}");
                
                // Generate invoice number
                $lastInvoice = Invoices::orderBy('id', 'desc')->first();
                $lastNumber = $lastInvoice ? intval(substr($lastInvoice->invoice_number, 4, 4)) : 0;
                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                $invoiceNumber = 'INV/' . $newNumber . '/' . date('Y');
                
                Log::info("API: Generated invoice number: {$invoiceNumber}");
                
                // Get current month and year
                $currentMonth = date('F'); 
                $currentYear = date('Y');
                $currentDate = date('d-m-y');
                
                // Create invoice record
                $invoice = Invoices::create([
                    'invoice_number' => $invoiceNumber,
                    'hotel_id' => $hotelId,
                    'month' => $currentMonth,
                    'year' => $currentYear,
                    'issue_date' => $currentDate,
                    'due_date' => $currentDate,
                    'total_amount' => $totalAmount,
                    'status' => 'draft',
                ]);
                
                Log::info("API: Created invoice ID: {$invoice->id} for hotel {$hotelId}");
                $invoices[] = $invoice->invoice_number;
            }
            
            Log::info("API: Trip completion process successful for trip {$id}. Generated " . count($invoices) . " invoices");
            
            return response()->json([
                'success' => true,
                'message' => 'Trip completed successfully',
                'data' => [
                    'trip_id' => $trip->id,
                    'bill_number' => $trip->bill_number,
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