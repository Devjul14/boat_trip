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


}

