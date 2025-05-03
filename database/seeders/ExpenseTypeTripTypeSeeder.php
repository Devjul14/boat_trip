<?php

namespace Database\Seeders;

use App\Models\ExpenseType;
use App\Models\TripType;
use App\Models\ExpenseTypeTripType;
use Illuminate\Database\Seeder;

class ExpenseTypeTripTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Define default charges for each combination of trip type and expense type
        $defaultCharges = [
            // Format: [trip_type_name, expense_type_code, default_charge]
            ['Vaavu Trip', 'BOAT_FEE', 25.00],
            ['Vaavu Trip', 'EXCURSION_FEE', 15.00],
            ['Vaavu Trip', 'PETROL_CHARGE', 5.00],
            ['Vaavu Trip', 'CHARTER_FEE', 2.00],
            
            ['Whale Shark Trip', 'BOAT_FEE', 40.00],
            ['Whale Shark Trip', 'EXCURSION_FEE', 30.00],
            ['Whale Shark Trip', 'PETROL_CHARGE', 8.00],
            ['Whale Shark Trip', 'CHARTER_FEE', 12.00],

            ['Manta Trip', 'BOAT_FEE', 25.00],
            ['Manta Trip', 'EXCURSION_FEE', 15.00],
            ['Manta Trip', 'PETROL_CHARGE', 5.00],
            ['Manta Trip', 'CHARTER_FEE', 2.00],

            ['Snorkel Trip', 'BOAT_FEE', 25.00],
            ['Snorkel Trip', 'EXCURSION_FEE', 15.00],
            ['Snorkel Trip', 'PETROL_CHARGE', 5.00],
            ['Snorkel Trip', 'CHARTER_FEE', 2.00],
            
            ['Dolphin Trip', 'BOAT_FEE', 40.00],
            ['Dolphin Trip', 'EXCURSION_FEE', 30.00],
            ['Dolphin Trip', 'PETROL_CHARGE', 8.00],
            ['Dolphin Trip', 'CHARTER_FEE', 12.00],
        ];

        foreach ($defaultCharges as [$tripTypeName, $expenseTypeCode, $charge]) {
            // Find the trip type
            $tripType = TripType::where('name', $tripTypeName)->first();
            
            // Find the expense type
            $expenseType = ExpenseType::where('code', $expenseTypeCode)->first();
            
            if (!$tripType || !$expenseType) {
                $this->command->warn("Could not find trip type '{$tripTypeName}' or expense type '{$expenseTypeCode}'");
                continue;
            }

            // Create the master default relationship
            ExpenseTypeTripType::updateOrCreate(
                [
                    'trip_type_id' => $tripType->id,
                    'expense_type_id' => $expenseType->id,
                    'is_master' => true,
                    'trip_id' => null,
                ],
                [
                    'default_charge' => $charge,
                ]
            );
        }

        $this->command->info('Master default charges have been seeded!');
    }
}