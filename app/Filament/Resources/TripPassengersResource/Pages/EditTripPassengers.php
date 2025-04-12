<?php

namespace App\Filament\Resources\TripPassengersResource\Pages;

use App\Filament\Resources\TripPassengersResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTripPassengers extends EditRecord
{
    protected static string $resource = TripPassengersResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
