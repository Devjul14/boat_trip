<?php

namespace App\Filament\Resources\TripTypeResource\Pages;

use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ExpenseType;

/**
 * Base trait for handling default charges in Trip Types
 */
trait HandlesTripTypeCharges
{
    /**
     * Save default charges from form data to the pivot table
     * 
     * @return void
     */
    protected function saveDefaultCharges(): void
    {
        try {
            // Get all charges from form state
            $charges = $this->form->getState()['charges'] ?? [];
            
            Log::info('Saving default charges for Trip Type ID: ' . $this->record->id);
            
            // Begin transaction to ensure data integrity
            DB::beginTransaction();
            
            foreach ($charges as $expenseTypeId => $charge) {
                // Normalize the charge: convert empty strings or nulls to 0.00 as float
                $charge = ($charge === null || $charge === '') ? 0.00 : (float) $charge;
                
                // Update or insert record in pivot table
                DB::table('expense_type_trip_types')->updateOrInsert(
                    [
                        'trip_type_id' => $this->record->id,
                        'expense_type_id' => $expenseTypeId,
                        'is_master' => true,
                        'trip_id' => null,
                    ],
                    [
                        'default_charge' => $charge,
                        'updated_at' => now(),
                        'created_at' => DB::raw('IFNULL(created_at, NOW())'), // Only set created_at if it's a new record
                    ]
                );
                
                Log::debug("Saved charge for trip_type_id={$this->record->id}, expense_type_id={$expenseTypeId}, charge={$charge}");
            }
            
            // Commit the transaction
            DB::commit();
            
            Log::info('Successfully saved all default charges for Trip Type ID: ' . $this->record->id);
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            Log::error('Failed to save default charges: ' . $e->getMessage(), [
                'trip_type_id' => $this->record->id,
                'exception' => $e,
            ]);
            
            // Optionally rethrow the exception or handle it as needed
            throw $e;
        }
    }
    
    /**
     * Retrieve existing charges for a trip type
     * 
     * @return array
     */
    protected function getExistingCharges(): array
    {
        // First get all active expense types to ensure we have a complete set
        $activeExpenseTypeIds = ExpenseType::where('active', true)->pluck('id')->toArray();
        
        // Get existing charge values
        $existingCharges = DB::table('expense_type_trip_types')
            ->where('trip_type_id', $this->record->id)
            ->where('is_master', true)
            ->whereNull('trip_id')
            ->pluck('default_charge', 'expense_type_id')
            ->toArray();
            
        // Build complete array with default 0 values for missing expense types
        $completeCharges = [];
        foreach ($activeExpenseTypeIds as $expenseTypeId) {
            $completeCharges[$expenseTypeId] = $existingCharges[$expenseTypeId] ?? 0;
        }
        
        return $completeCharges;
    }
}