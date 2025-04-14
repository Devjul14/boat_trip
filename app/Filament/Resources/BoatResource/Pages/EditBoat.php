<?php

namespace App\Filament\Resources\BoatResource\Pages;

use App\Filament\Resources\BoatResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBoat extends EditRecord
{
    protected static string $resource = BoatResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!isset($data['user_id']) || empty($data['user_id'])) {
            $data['user_id'] = auth()->id();
        }
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
