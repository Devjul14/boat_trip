<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TripPassenger;
use App\Models\Trip;
use App\Models\Hotel;
use App\Models\TripType;

class TripPassengerSeeder extends Seeder
{
    public function run(): void
    {
        $trips = Trip::where('status', 'completed')->get();
        $hotelIds = Hotel::pluck('id')->toArray();

        foreach ($trips as $trip) {
            $tripType = TripType::find($trip->trip_type_id);
            
            // Each trip can have passengers from 1-3 hotels
            $numHotels = rand(1, 3);
            $selectedHotels = array_rand(array_flip($hotelIds), $numHotels);
            if (!is_array($selectedHotels)) {
                $selectedHotels = [$selectedHotels];
            }
            
            foreach ($selectedHotels as $hotelId) {
                $numPassengers = rand(2, 8);
                $excursionCharge = $tripType->default_excursion_charge;
                $boatCharge = $tripType->default_boat_charge;
                $charterCharge = $numPassengers < 4 ? $tripType->default_charter_charge : 0;
                
                $totalUsd = ($excursionCharge + $boatCharge) * $numPassengers + $charterCharge;
                $totalRf = $totalUsd * 15.42; // Example conversion rate
                
                TripPassengers::create([
                    'trip_id' => $trip->id,
                    'hotel_id' => $hotelId,
                    'number_of_passengers' => $numPassengers,
                    'excursion_charge' => $excursionCharge,
                    'boat_charge' => $boatCharge,
                    'charter_charge' => $charterCharge,
                    'total_usd' => $totalUsd,
                    'total_rf' => $totalRf,
                    'payment_status' => rand(0, 3) > 0 ? 'paid' : 'pending',
                    'payment_method' => rand(0, 1) ? 'cash' : 'bank transfer',
                ]);
            }
        }
    }
}