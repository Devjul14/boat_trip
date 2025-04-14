<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Trip;
use App\Models\Hotel;
use App\Models\Invoices;
use App\Models\InvoiceItem;
use App\Models\TripPassengers;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $hotels = Hotel::all();
        $currentDate = Carbon::now();
        
        foreach ($hotels as $hotel) {
            // Create an invoice for the previous month
            $month = $currentDate->copy()->subMonth()->month;
            $year = $currentDate->copy()->subMonth()->year;
            
            // Check if there are trips for this hotel in the previous month
            $passengers = TripPassengers::whereHas('trip', function ($query) use ($month, $year) {
                $query->whereMonth('date', $month)
                      ->whereYear('date', $year)
                      ->where('status', 'completed');
            })->where('hotel_id', $hotel->id)->get();
            
            if ($passengers->count() > 0) {
                // Create the invoice
                $invoiceNumber = 'INV-' . $year . $month . '-' . str_pad($hotel->id, 3, '0', STR_PAD_LEFT);
                $totalAmount = $passengers->sum('total_usd');
                
                $invoice = Invoices::create([
                    'invoice_number' => $invoiceNumber,
                    'hotel_id' => $hotel->id,
                    'month' => $month,
                    'year' => $year,
                    'issue_date' => $currentDate->copy()->subDays(rand(5, 15)),
                    'due_date' => $currentDate->copy()->addDays(rand(10, 30)),
                    'total_amount' => $totalAmount,
                    'status' => rand(0, 2) === 0 ? 'draft' : (rand(0, 1) ? 'sent' : 'paid'),
                ]);
                
                // Create invoice items
                foreach ($passengers as $passenger) {
                    $trip = Trip::find($passenger->trip_id);
                    
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'trip_id' => $trip->id,
                        'description' => $trip->tripType->name . ' Trip - ' . $trip->date,
                        'number_of_passengers' => $passenger->number_of_passengers,
                        'excursion_charge' => $passenger->excursion_charge,
                        'boat_charge' => $passenger->boat_charge,
                        'charter_charge' => $passenger->charter_charge,
                        'total_amount' => $passenger->total_usd,
                    ]);
                }
            }
        }
    }
}
