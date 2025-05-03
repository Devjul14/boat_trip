<?php

namespace App\Filament\Resources\TripTypeResource\Pages;

use App\Filament\Resources\TripTypeResource;
use App\Models\ExpenseTypeTripType;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateTripType extends CreateRecord
{
    protected static string $resource = TripTypeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Extract the charges data before creating the record
        $charges = $data['charges'] ?? [];
        unset($data['charges']);

        // Create the trip type
        $record = static::getModel()::create($data);

        // Create the default charges for each expense type
        foreach ($charges as $expenseTypeId => $charge) {
            ExpenseTypeTripType::create([
                'trip_type_id' => $record->id,
                'expense_type_id' => $expenseTypeId,
                'default_charge' => $charge,
                'is_master' => true,
                'trip_id' => null, // Master defaults have no trip_id
            ]);
        }

        return $record;
    }
}