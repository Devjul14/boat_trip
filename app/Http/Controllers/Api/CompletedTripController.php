<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Ticket;
use App\Models\TicketExpense;
use App\Models\Hotel;
use App\Models\Invoices;
use App\Models\InvoiceItems;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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

            // Use database transaction to ensure data consistency
            DB::beginTransaction();
            
            try {
                // Update trip status
                $trip->update(['status' => 'completed']);
                Log::info("API: Trip status updated to 'completed'");
                
                // Get all unpaid tickets for this trip
                $unpaidTickets = $trip->ticket()
                    ->where('payment_status', 'unpaid')
                    ->get();
                
                if ($unpaidTickets->isEmpty()) {
                    Log::info("API: No unpaid tickets found for trip {$id}");
                    DB::commit();
                    return response()->json([
                        'success' => true,
                        'message' => 'Trip completed successfully. No unpaid tickets found.',
                        'data' => [
                            'trip_id' => $trip->id,
                            'date' => $trip->date,
                            'invoices' => []
                        ]
                    ], 200);
                }
                
                // Group unpaid tickets by hotel
                $ticketsByHotel = $unpaidTickets->groupBy('hotel_id');
                Log::info("API: Found " . count($ticketsByHotel) . " hotels with unpaid tickets");
                
                $invoiceCount = 0;
                $invoices = [];
                
                // Calculate issue date from trip date
                $issueDate = $trip->date->format('Y-m-d');
                $dueDate = date('Y-m-d', strtotime($issueDate . ' + 7 days'));
                Log::info("API: Issue date: {$issueDate}, Due date: {$dueDate}");
                
                foreach ($ticketsByHotel as $hotelId => $tickets) {
                    if (!$hotelId) {
                        Log::info("API: Skipping tickets with null hotel ID");
                        continue;
                    }
                    
                    $hotel = Hotel::find($hotelId);
                    $hotelName = $hotel ? $hotel->name : "Unknown Hotel";
                    Log::info("API: Processing hotel ID: {$hotelId} ({$hotelName})");
                    
                    // Generate invoice number
                    $lastInvoice = Invoices::orderBy('id', 'desc')->first();
                    $lastNumber = $lastInvoice ? intval(substr($lastInvoice->invoice_number, 8, 3)) : 0;
                    $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
                    $invoiceNumber = 'AK/' . date('Y') . '/' . $newNumber;
                    Log::info("API: Generated invoice number: {$invoiceNumber}");
                    
                    // Create invoice record
                    $invoice = Invoices::create([
                        'invoice_number' => $invoiceNumber,
                        'hotel_id' => $hotelId,
                        'trip_id' => $trip->id,
                        'month' => date('F'),
                        'year' => date('Y'),
                        'issue_date' => $issueDate,
                        'due_date' => $dueDate,
                        'total_amount' => 0, // Will be calculated after adding items
                        'status' => 'unpaid',
                    ]);
                    Log::info("API: Created invoice ID: {$invoice->id} for hotel {$hotelId}");
                    
                    $grandTotal = 0;
                    $itemCount = 0;
                    
                    // Add each unpaid ticket as an invoice item
                    foreach ($tickets as $ticket) {
                        // Calculate ticket total amount (including expenses)
                        $ticketTotal = $ticket->total_amount; // This uses the calculated attribute
                        
                        // Create invoice item
                        $invoiceItem = InvoiceItems::create([
                            'invoice_id' => $invoice->id,
                            'ticket_id' => $ticket->id,
                            'description' => "Trip ticket for {$ticket->number_of_passengers} passenger(s) - Trip Date: {$trip->date->format('Y-m-d')}",
                            'quantity' => $ticket->number_of_passengers,
                            'unit_price' => $ticketTotal / $ticket->number_of_passengers, // Per passenger cost
                            'total_amount' => $ticketTotal,
                        ]);
                        
                        $grandTotal += $ticketTotal;
                        $itemCount++;
                        
                        // Update ticket to associate it with the invoice
                        $ticket->update(['invoice_id' => $invoice->id]);
                        
                        Log::info("API: Added ticket {$ticket->id} as invoice item. Amount: {$ticketTotal}");
                    }
                    
                    // Update invoice with grand total
                    $invoice->update(['total_amount' => $grandTotal]);
                    Log::info("API: Updated invoice {$invoice->id} with grand total: {$grandTotal}");
                    
                    $invoiceCount++;
                    $invoices[] = [
                        'invoice_number' => $invoice->invoice_number,
                        'hotel_name' => $hotelName,
                        'total_amount' => $grandTotal,
                        'items_count' => $itemCount
                    ];
                }
                
                DB::commit();
                Log::info("API: Trip completion process finished. Generated {$invoiceCount} invoices");
                
                return response()->json([
                    'success' => true,
                    'message' => "Trip completed successfully. Generated {$invoiceCount} invoice(s) for unpaid tickets.",
                    'data' => [
                        'trip_id' => $trip->id,
                        'date' => $trip->date->format('Y-m-d'),
                        'invoices' => $invoices
                    ]
                ], 200);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
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