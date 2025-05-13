<?php 

// File: app/Filament/Resources/TicketResource/Pages/EditTicket.php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Filament\Resources\Pages\EditRecord;
use App\Models\Trip;
use Illuminate\Support\Facades\Log;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;
    
   
}