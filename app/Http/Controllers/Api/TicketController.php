<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;

class TicketController extends Controller
{
    public function search(Request $request)
{
    $query = Ticket::query()->with([
        'trip',
        'trip.tripType',
        'trip.boat',
        'trip.boatman',
        'hotel'
    ]); 

    if ($request->filled('hotel_id') && is_numeric($request->hotel_id)) {
        $query->where('hotel_id', $request->hotel_id);
    }

    if ($request->filled('payment_method')) {
        $query->where('payment_method', $request->payment_method);
    }

    if ($request->filled('payment_status')) {
        $query->where('payment_status', $request->payment_status);
    }

    $tickets = $query->get();

    return response()->json([
        'success' => true,
        'message' => 'Search results',
        'data' => $tickets
    ]);
}

public function statusUpdate(Request $request, int $id)
{
    try {
        // Validate the request
        $validated = $request->validate([
            'payment_status' => 'required|string|in:pending,paid,cancelled',
            'payment_method' => 'nullable|string|in:cash,credit_card,bank_transfer',
        ]);

        // Find and update the ticket
        $ticket = Ticket::findOrFail($id);
        
        $ticket->update([
            'payment_status' => $validated['payment_status'],
            'payment_method' => $validated['payment_method'] ?? $ticket->payment_method,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket payment status updated successfully',
            'data' => $ticket->fresh(), // This will include the total_amount automatically
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update ticket payment status',
            'error' => $e->getMessage(),
        ], 500);
    }
}




}

