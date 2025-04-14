<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Trip;
use App\Models\User;
use App\Models\Boat;
use App\Models\TripType;
use Carbon\Carbon;

class TripSeeder extends Seeder
{
    public function run(): void
    {
        // Get boatmen IDs
        $boatmenIds = User::where('role', 'boatman')->pluck('id')->toArray();
        $boatIds = Boat::pluck('id')->toArray();
        $tripTypeIds = TripType::pluck('id')->toArray();

        // Create 30 trips over the past 3 months
        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->subDays(rand(0, 90))->format('Y-m-d');
            $billNumber = 'AK/2025/' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
            
            Trip::create([
                'date' => $date,
                'bill_number' => $billNumber,
                'trip_type_id' => $tripTypeIds[array_rand($tripTypeIds)],
                'boat_id' => $boatIds[array_rand($boatIds)],
                'boatman_id' => $boatmenIds[array_rand($boatmenIds)],
                'remarks' => $i % 5 === 0 ? 'Special trip with VIP guests' : null,
                'status' => rand(0, 10) > 2 ? 'completed' : (rand(0, 1) ? 'scheduled' : 'cancelled'),
                'petrol_consumed' => rand(10, 50),
                'petrol_filled' => rand(5, 30),
            ]);
        }
    }
}
