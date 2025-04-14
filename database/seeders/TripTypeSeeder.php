<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TripType;

class TripTypeSeeder extends Seeder
{
    public function run(): void
    {
        $tripTypes = [
            [
                'name' => 'Whale Shark Trip',
                'description' => 'A trip to see whale sharks in their natural habitat.',
                'default_excursion_charge' => 55.00,
                'default_boat_charge' => 30.00,
                'default_charter_charge' => 250.00,
            ],
            [
                'name' => 'Manta Trip',
                'description' => 'A trip to see manta rays in their natural habitat.',
                'default_excursion_charge' => 50.00,
                'default_boat_charge' => 25.00,
                'default_charter_charge' => 230.00,
            ],
            [
                'name' => 'Vaavu Trip',
                'description' => 'A trip to Vaavu Atoll for snorkeling and diving.',
                'default_excursion_charge' => 60.00,
                'default_boat_charge' => 35.00,
                'default_charter_charge' => 280.00,
            ],
            [
                'name' => 'Turtle Trip',
                'description' => 'A trip to see sea turtles in their natural habitat.',
                'default_excursion_charge' => 45.00,
                'default_boat_charge' => 25.00,
                'default_charter_charge' => 220.00,
            ],
            [
                'name' => 'Dolphin Trip',
                'description' => 'A trip to see dolphins in the open sea.',
                'default_excursion_charge' => 40.00,
                'default_boat_charge' => 25.00,
                'default_charter_charge' => 210.00,
            ],
        ];

        foreach ($tripTypes as $tripType) {
            TripType::create($tripType);
        }
    }
}
