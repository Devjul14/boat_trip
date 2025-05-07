<?php

namespace App\Filament\Resources\TripTypeResource\Pages;

use App\Filament\Resources\TripTypeResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditTripType extends EditRecord
{
    use HandlesTripTypeCharges;
    
    protected static string $resource = TripTypeResource::class;

    /**
     * Preload charges into form data before filling the form
     * 
     * @param array $data
     * @return array
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['charges'] = $this->getExistingCharges();
        
        Log::debug('Preloaded charges for Trip Type ID: ' . $this->record->id, [
            'charges' => $data['charges']
        ]);
        
        return $data;
    }

    /**
     * Actions after record is saved
     * 
     * @return void
     */
    protected function afterSave(): void
    {
        Log::info('Trip Type updated with ID: ' . $this->record->id);
        $this->saveDefaultCharges();
    }
}