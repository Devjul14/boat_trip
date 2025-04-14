<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hotel;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        $hotels = [
            [
                'name' => 'Beach Hotel Dhigurah',
                'contact_person' => 'Ahmed Mohamed',
                'email' => 'contact@beachhoteldhigurah.com',
                'phone' => '+960 7894561',
                'address' => 'Dhigurah Island, Alif Dhaal Atoll, Maldives',
                'payment_terms' => 'Net 30 days',
            ],
            [
                'name' => 'Sea Side Resort',
                'contact_person' => 'Ibrahim Rasheed',
                'email' => 'bookings@seasideresort.com',
                'phone' => '+960 7651234',
                'address' => 'Maafushi Island, Kaafu Atoll, Maldives',
                'payment_terms' => 'Net 15 days',
            ],
            [
                'name' => 'Oasis Beach & Spa',
                'contact_person' => 'Aminath Shareef',
                'email' => 'reservations@oasisbeach.com',
                'phone' => '+960 7893214',
                'address' => 'Hulhumale, Maldives',
                'payment_terms' => 'Payment in advance',
            ],
            [
                'name' => 'Sunset View Inn',
                'contact_person' => 'Hassan Ali',
                'email' => 'info@sunsetviewinn.com',
                'phone' => '+960 7896547',
                'address' => 'Thulusdhoo Island, Kaafu Atoll, Maldives',
                'payment_terms' => 'Net 45 days',
            ],
            [
                'name' => 'Paradise Island Resort',
                'contact_person' => 'Fathimath Latheef',
                'email' => 'bookings@paradiseisland.com',
                'phone' => '+960 7654321',
                'address' => 'North Male Atoll, Maldives',
                'payment_terms' => 'Net 30 days',
            ],
        ];

        foreach ($hotels as $hotel) {
            Hotel::create($hotel);
        }
    }
}
