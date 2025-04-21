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

    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'payment_terms',
    ];

     public function invoices(): HasMany
     {
         return $this->hasMany(Invoice::class);
     }
 
     public function tripPassengers(): HasMany
     {
         return $this->hasMany(TripPassengers::class);
     }
}
