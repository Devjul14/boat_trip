<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            HotelSeeder::class,
            TripTypeSeeder::class,
            BoatSeeder::class,
            TripSeeder::class,
            TripPassengerSeeder::class,
            ExpenseSeeder::class,
            InvoiceSeeder::class,
        ]);
    }
}
