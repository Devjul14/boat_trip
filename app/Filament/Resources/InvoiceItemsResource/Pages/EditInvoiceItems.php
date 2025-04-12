<?php

namespace App\Filament\Resources\InvoiceItemsResource\Pages;

use App\Filament\Resources\InvoiceItemsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoiceItems extends EditRecord
{
    protected static string $resource = InvoiceItemsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
