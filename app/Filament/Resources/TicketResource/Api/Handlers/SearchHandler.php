<?php

namespace App\Filament\Resources\TicketResource\Api\Handlers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\TicketResource;

class SearchHandler extends Handlers
{
    public static string | null $uri = '/search';
    public static string | null $resource = TicketResource::class;

    public static function getMethod()
    {
        return Handlers::GET;
    }

    public static function getModel()
    {
        return static::$resource::getModel();
    }

    /**
     * Search Ticket
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(Request $request)
    {
        $query = Ticket::query()
            ->with(['trip', 'trip.tripType', 'trip.boat', 'trip.boatman']); // eager load

        // Filter opsional
        if ($request->filled('date')) {
            $query->whereHas('trip', function ($q) use ($request) {
                $q->where('date', $request->date);
            });
        }

        if ($request->filled('trip_type_id')) {
            $query->whereHas('trip', function ($q) use ($request) {
                $q->where('trip_type_id', $request->trip_type_id);
            });
        }

        if ($request->filled('boat_id')) {
            $query->whereHas('trip', function ($q) use ($request) {
                $q->where('boat_id', $request->boat_id);
            });
        }

        if ($request->filled('boatman_id')) {
            $query->whereHas('trip', function ($q) use ($request) {
                $q->where('boatman_id', $request->boatman_id);
            });
        }

        if ($request->filled('status')) {
            $query->whereHas('trip', function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        // Eksekusi query
        $tickets = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Search result',
            'data'    => $tickets
        ]);
    }
}
