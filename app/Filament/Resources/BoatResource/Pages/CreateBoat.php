<?php

namespace App\Filament\Resources\BoatResource\Pages;

use App\Filament\Resources\BoatResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBoat extends CreateRecord
{
    protected static string $resource = BoatResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pastikan user_id terisi
        $data['user_id'] = auth()->id();
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
