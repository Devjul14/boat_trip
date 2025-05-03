<?php

namespace App\Filament\Resources\TripTypeResource\Pages;

use App\Filament\Resources\TripTypeResource;
use App\Models\ExpenseTypeTripType;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EditTripType extends EditRecord
{
    protected static string $resource = TripTypeResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Extract charges from form data and store in session or process here
        if (isset($data['charges'])) {
            Log::info('Charges data before update:', $data['charges']);
            session()->put('trip_type_charges', $data['charges']);
            unset($data['charges']);
        } else {
            Log::info('No charges data found in form submission.');
        }
        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record; // The updated record instance
        $charges = session()->pull('trip_type_charges', []);
        Log::info('Processing charges after update:', $charges);

        if (!empty($charges)) {
            foreach ($charges as $expenseTypeId => $charge) {
                $expenseTypeId = (int)$expenseTypeId;
                $chargeValue = floatval($charge);

                Log::info("Updating expense type {$expenseTypeId} with charge {$chargeValue}");

                DB::table('expense_type_trip_types')
                    ->updateOrInsert(
                        [
                            'trip_type_id' => $record->id,
                            'expense_type_id' => $expenseTypeId,
                            'is_master' => true,
                            'trip_id' => null,
                        ],
                        [
                            'default_charge' => $chargeValue,
                            'updated_at' => now(),
                        ]
                    );

                Log::info("Updated record for expense type {$expenseTypeId}");
            }

            $record->refresh();

            $finalCharges = DB::table('expense_type_trip_types')
                ->where('trip_type_id', $record->id)
                ->where('is_master', true)
                ->whereNull('trip_id')
                ->get(['expense_type_id', 'default_charge']);

            Log::info('Final state after update:', json_decode(json_encode($finalCharges), true));
        } else {
            Log::info('No charges to process.');
        }
    }
}