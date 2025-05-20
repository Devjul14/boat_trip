<?php

namespace App\Filament\Resources\TripResource\Pages;

use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Infolists\Components\BadgeEntry;
use App\Filament\Resources\TripResource;
use Filament\Resources\Pages\ViewRecord;
use Infolists\Components\RepeatableEntry;

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
                Infolists\Components\Section::make('Trip Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('date')
                            ->date(),
                        Infolists\Components\TextEntry::make('bill_number'),
                        Infolists\Components\TextEntry::make('tripType.name')
                            ->label('Trip Type'),
                        Infolists\Components\TextEntry::make('boat.name')
                            ->label('Boat'),
                        Infolists\Components\TextEntry::make('boatman.name')
                            ->label('Boatman'),
                        Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state): string => 
                            match ($state) {
                                'cancelled' => 'danger',
                                'scheduled' => 'warning',
                                'completed' => 'success',
                                default => 'gray',
                            }
                        ),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Remarks')
                    ->schema([
                        Infolists\Components\TextEntry::make('remarks')
                            ->markdown()
                            ->columnSpanFull(),
                    ]),
                    
            ]);
    }
}