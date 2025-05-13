<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $table = 'expense';

    protected $fillable = [
        'name',
    ];

    public function tripTypes()
    {
        return $this->belongsToMany(TripType::class, 'expense_trip_types', 'expense_id', 'trip_type_id');
    }
}

