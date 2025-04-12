<?php

namespace App\Filament\Resources\TripTypeResource\Pages;

use App\Filament\Resources\TripTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTripType extends EditRecord
{
    protected static string $resource = TripTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
