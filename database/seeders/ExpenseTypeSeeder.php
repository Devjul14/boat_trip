<?php

namespace Database\Seeders;

use App\Models\ExpenseType;
use Illuminate\Database\Seeder;

class ExpenseTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $expenseTypes = [
            [
                'name' => 'Boat Fee',
                'description' => 'Fee for boat usage during the trip.',
                'code' => 'BOAT_FEE',
                'active' => true,
            ],
            [
                'name' => 'Excursion Fee',
                'description' => 'Fee for excursion activities.',
                'code' => 'EXCURSION_FEE',
                'active' => true,
            ],
            [
                'name' => 'Petrol Charge',
                'description' => 'Charge for petrol consumption.',
                'code' => 'PETROL_CHARGE',
                'active' => true,
            ],
            [
                'name' => 'Charter Fee',
                'description' => 'Fee for private charter service.',
                'code' => 'CHARTER_FEE',
                'active' => true,
            ],
        ];

        foreach ($expenseTypes as $expenseType) {
            ExpenseType::updateOrCreate(
                ['code' => $expenseType['code']],
                $expenseType
            );
        }

        $this->command->info('Expense types have been seeded!');
    }
}