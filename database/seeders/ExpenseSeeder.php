<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Expense;
use App\Models\Trip;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $trips = Trip::where('status', 'completed')->get();
        $expenseTypes = ['petrol', 'food', 'maintenance', 'crew', 'supplies'];

        foreach ($trips as $trip) {
            // Add 1-3 expenses per trip
            $numExpenses = rand(1, 3);
            
            for ($i = 0; $i < $numExpenses; $i++) {
                $expenseType = $expenseTypes[array_rand($expenseTypes)];
                $amount = 0;
                
                switch ($expenseType) {
                    case 'petrol':
                        $amount = $trip->petrol_consumed * rand(2, 5);
                        break;
                    case 'food':
                        $amount = rand(20, 50);
                        break;
                    case 'maintenance':
                        $amount = rand(50, 200);
                        break;
                    case 'crew':
                        $amount = rand(30, 100);
                        break;
                    case 'supplies':
                        $amount = rand(10, 30);
                        break;
                }
                
                Expenses::create([
                    'trip_id' => $trip->id,
                    'expense_type' => $expenseType,
                    'amount' => $amount,
                    'notes' => $expenseType === 'maintenance' ? 'Emergency repair' : null,
                ]);
            }
        }
    }
}
