<?php

namespace App\Filament\Resources\TripResource\Pages;

use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Infolists\Components\BadgeEntry;
use App\Filament\Resources\TripResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\RepeaterEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;

class ViewTrip extends ViewRecord
{
    protected static string $resource = TripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => $record->status === 'scheduled'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Trip Information')
                    ->schema([
                        TextEntry::make('date')
                            ->date(),
                        TextEntry::make('tripType.name')
                            ->label('Trip Type'),
                        TextEntry::make('boat.name')
                            ->label('Boat'),
                        TextEntry::make('boatman.name')
                            ->label('Boatman'),
                        TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state): string => 
                            match ($state) {
                                'cancelled' => 'danger',
                                'scheduled' => 'warning',
                                'completed' => 'success',
                                default => 'gray',
                            }
                        ),
                        TextEntry::make('remarks')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                
                Section::make('Invoices')
                ->schema([
                    RepeatableEntry::make('invoices')
                        ->schema([
                            TextEntry::make('invoice_number')->label('Invoice #'),
                            TextEntry::make('hotel.name')->label('Hotel'),
                            TextEntry::make('month'),
                            TextEntry::make('year'),
                            TextEntry::make('issue_date')->date(),
                            TextEntry::make('due_date')->date(),
                            TextEntry::make('total_amount')->money('usd'), // ganti 'usd' jika perlu
                            TextEntry::make('status')->badge(),
                        ])
                        ->columns(4)
                        ->label('Invoices')
                        ->columnSpanFull()
                        ->visible(fn ($record) => $record->invoices->isNotEmpty()),
                ]),
                    
            ]);
    }
}