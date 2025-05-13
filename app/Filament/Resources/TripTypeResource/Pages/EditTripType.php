<?php

namespace App\Filament\Resources\TripTypeResource\Pages;

use App\Filament\Resources\TripTypeResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditTripType extends EditRecord
{
    
    protected static string $resource = TripTypeResource::class;
}