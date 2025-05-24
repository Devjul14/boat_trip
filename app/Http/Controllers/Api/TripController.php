<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trip;

class TripController extends Controller
{
    public function search(Request $request)
    {
        $query = Trip::query()->with(['tripType', 'boat', 'boatman']);

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        if ($request->filled('trip_type_id')) {
            $query->where('trip_type_id', $request->trip_type_id);
        }

        if ($request->filled('boatman_id')) {
            $query->where('boatman_id', $request->boatman_id);
        }

        if ($request->filled('boat_id')) {
            $query->where('boat_id', $request->boat_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $trips = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Search results',
            'data' => $trips
        ]);
    }
}
