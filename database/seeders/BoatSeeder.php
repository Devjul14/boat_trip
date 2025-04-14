<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Boat;

class BoatSeeder extends Seeder
{
    public function run(): void
    {
        $boats = [
            [
                'name' => 'Blue Whale',
                'capacity' => 15,
                'registration_number' => 'BT-2025-001',
            ],
            [
                'name' => 'Dolphin',
                'capacity' => 10,
                'registration_number' => 'BT-2025-002',
            ],
            [
                'name' => 'Sea Turtle',
                'capacity' => 12,
                'registration_number' => 'BT-2025-003',
            ],
            [
                'name' => 'Manta Ray',
                'capacity' => 20,
                'registration_number' => 'BT-2025-004',
            ],
            [
                'name' => 'Coral Explorer',
                'capacity' => 18,
                'registration_number' => 'BT-2025-005',
            ],
        ];

        foreach ($boats as $boat) {
            Boat::create($boat);
        }
    }
}
