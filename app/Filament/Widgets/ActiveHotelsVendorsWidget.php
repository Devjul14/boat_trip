<?php

namespace App\Filament\Widgets;

use App\Models\Hotel;
use App\Models\Trip;
use App\Models\TripPassengers;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ActiveHotelsVendorsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected function getStats(): array
    {
        // Get current date
        $now = now();
        
        // Define date ranges
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $thirtyDaysAhead = $now->copy()->addDays(30);
        
        // Get total hotels
        $totalHotels = Hotel::count();
        
        // Get hotels with recent trips (last 30 days)
        $hotelsWithRecentTrips = Hotel::whereIn('id', function($query) use ($thirtyDaysAgo, $now) {
            $query->select('hotel_id')
                ->from('tickets')
                ->join('trips', 'trips.id', '=', 'tickets.trip_id')
                ->whereDate('trips.date', '>=', $thirtyDaysAgo)
                ->whereDate('trips.date', '<=', $now)
                ->distinct();
        })->count();
        
        // Get hotels with upcoming trips (next 30 days)
        $hotelsWithUpcomingTrips = Hotel::whereIn('id', function($query) use ($now, $thirtyDaysAhead) {
            $query->select('hotel_id')
                ->from('tickets')
                ->join('trips', 'trips.id', '=', 'tickets.trip_id')
                ->whereDate('trips.date', '>', $now)
                ->whereDate('trips.date', '<=', $thirtyDaysAhead)
                ->distinct();
        })->count();
        
        // Get active hotels (with activity in last 60 days)
        $activeHotels = Hotel::whereIn('id', function($query) use ($thirtyDaysAgo, $thirtyDaysAhead) {
            $query->select('hotel_id')
                ->from('tickets')
                ->join('trips', 'trips.id', '=', 'tickets.trip_id')
                ->whereDate('trips.date', '>=', $thirtyDaysAgo)
                ->whereDate('trips.date', '<=', $thirtyDaysAhead)
                ->distinct();
        })->count();
        
        $activePercentage = $totalHotels > 0 ? round(($activeHotels / $totalHotels) * 100) : 0;
        
        // Get top hotels by passenger count
        $topHotelsSubquery = DB::table('tickets')
            ->select('hotel_id', DB::raw('COUNT(*) as passenger_count'))
            ->join('trips', 'trips.id', '=', 'tickets.trip_id')
            ->whereDate('trips.date', '>=', $thirtyDaysAgo)
            ->groupBy('hotel_id')
            ->orderByDesc('passenger_count')
            ->limit(3);
            
        $topHotels = Hotel::joinSub($topHotelsSubquery, 'top_hotels', function ($join) {
                $join->on('hotels.id', '=', 'top_hotels.hotel_id');
            })
            ->select('hotels.*', 'top_hotels.passenger_count')
            ->orderByDesc('passenger_count')
            ->get();
        
        $topHotelsDescription = '';
        foreach ($topHotels as $index => $hotel) {
            $topHotelsDescription .= ($index + 1) . ". {$hotel->name} ({$hotel->passenger_count} passengers)\n";
        }
        
        if (empty($topHotelsDescription)) {
            $topHotelsDescription = 'No trip data in the last 30 days';
        }
        
        return [
            Stat::make('Total Hotels/Vendors', $totalHotels)
                ->description('Total number of hotels and vendors')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('gray'),
                
            Stat::make('Active Hotels', $activeHotels)
                ->description($activePercentage . '% of hotels active in the last 60 days')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('success')
                ->chart([
                    $activePercentage, 
                    100 - $activePercentage
                ]),
                
            Stat::make('Hotels with Recent Trips', $hotelsWithRecentTrips)
                ->description('In the last 30 days')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),
                
            Stat::make('Hotels with Upcoming Trips', $hotelsWithUpcomingTrips)
                ->description('In the next 30 days')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning'),
                
            Stat::make('Top Hotel/Vendor', $topHotels->count() > 0 ? $topHotels->first()->name : '-')
                ->description($topHotelsDescription)
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success'),
                
        ];
    }
}