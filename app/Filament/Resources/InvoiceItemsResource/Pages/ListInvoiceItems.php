<?php

namespace App\Filament\Resources\InvoiceItemsResource\Pages;

use App\Filament\Resources\InvoiceItemsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvoiceItems extends ListRecords
{
    protected static string $resource = InvoiceItemsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
