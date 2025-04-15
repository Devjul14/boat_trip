<?php

namespace App\Models;

use App\Models\Trip;
use App\Models\Invoices;
use App\Models\TripPassengers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hotel extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

     // Satu hotel dapat memiliki banyak invoice (tagihan bulanan)
     public function invoices(): HasMany
     {
         return $this->hasMany(Invoice::class);
     }
 
     // Satu hotel dapat memiliki banyak penumpang trip (tiket)
     public function tripPassengers(): HasMany
     {
         return $this->hasMany(TripPassenger::class);
     }
}
