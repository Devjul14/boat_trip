<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class CancelTripController extends Controller
{
    public function cancelTrip(Request $request, $id)
    {
        try {
            
            // Find the trip by ID
            $trip = Trip::findOrFail($id);
            $trip_id = $trip->id;
            
            // Check if trip is in scheduled status
            if ($trip->status !== 'scheduled') {
                Log::warning("API: Trip {$id} has status '{$trip->status}', not 'scheduled'");
                return response()->json([
                    'success' => false,
                    'message' => 'Only scheduled trips can be cancel',
                ], 400);
            }
            
            // Update trip status
            $trip->update(['status' => 'cancelled']);
            Log::info("API: Updated trip {$id} status to 'cancel'");
            
            return response()->json([
                'success' => true,
                'message' => 'Trip cancel successfully',
            ], 200);
            
        } catch (\Exception $e) {
            Log::error("API: Trip cancelation error for trip {$id}: " . $e->getMessage());
            Log::error("API: Exception stack trace: " . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel trip: ' . $e->getMessage()
            ], 500);
        }
            
    }
}
