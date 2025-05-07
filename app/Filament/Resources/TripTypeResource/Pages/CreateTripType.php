<?php

namespace App\Filament\Resources\TripTypeResource\Pages;

use App\Filament\Resources\TripTypeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateTripType extends CreateRecord
{
    use HandlesTripTypeCharges;
    
    protected static string $resource = TripTypeResource::class;

    /**
     * Actions after record is saved
     * 
     * @return void
     */
    protected function afterCreate(): void
    {
        Log::info('Trip Type created with ID: ' . $this->record->id);
        $this->saveDefaultCharges();
    }
}