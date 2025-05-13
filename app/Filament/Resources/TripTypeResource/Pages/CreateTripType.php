<?php

namespace App\Filament\Resources\TripTypeResource\Pages;

use App\Filament\Resources\TripTypeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateTripType extends CreateRecord
{   
    protected static string $resource = TripTypeResource::class;

}