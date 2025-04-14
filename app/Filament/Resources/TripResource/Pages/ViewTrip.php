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
            Actions\EditAction::make(),
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

                Infolists\Components\Section::make('Hotels & Passengers')
                ->schema(function ($record) {
                    $entries = [];
                    
                    // Buat entry untuk setiap trip passenger
                    foreach ($record->tripPassengers as $index => $passenger) {
                        $entries[] = Infolists\Components\Grid::make([$index])
                            ->schema([
                                Infolists\Components\TextEntry::make("tripPassengers.{$index}.hotel.name")
                                    ->label('Hotel'),
                                Infolists\Components\TextEntry::make("tripPassengers.{$index}.number_of_passengers")
                                    ->label('Passengers'),
                                Infolists\Components\TextEntry::make("tripPassengers.{$index}.excursion_charge")
                                    ->label('Excursion ($)')
                                    ->money('USD'),
                                Infolists\Components\TextEntry::make("tripPassengers.{$index}.boat_charge")
                                    ->label('Boat ($)')
                                    ->money('USD'),
                                Infolists\Components\TextEntry::make("tripPassengers.{$index}.charter_charge")
                                    ->label('Charter ($)')
                                    ->money('USD'),
                                Infolists\Components\TextEntry::make("tripPassengers.{$index}.total_usd")
                                    ->label('Total (USD)')
                                    ->money('USD'),
                                Infolists\Components\TextEntry::make("tripPassengers.{$index}.payment_status")
                                    ->label('Payment Status')
                                    ->badge()
                                    ->color(fn (string $state): string => 
                                        match ($state) {
                                            'pending' => 'danger',
                                            'paid' => 'success',
                                            default => 'gray',
                                        }
                                    ),
                            ])
                            ->columns(4);
                    }
                    
                    if (empty($entries)) {
                        $entries[] = Infolists\Components\TextEntry::make('no_passengers')
                            ->label('')
                            ->state('No passengers recorded for this trip.')
                            ->columnSpanFull();
                    }
                    
                    return $entries;
                }),

                Infolists\Components\Section::make('Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('tripPassengers_count')
                            ->label('Total Hotels')
                            ->state(function ($record) {
                                return $record->tripPassengers->count();
                            }),
                        Infolists\Components\TextEntry::make('total_passengers')
                            ->label('Total Passengers')
                            ->state(function ($record) {
                                return $record->tripPassengers->sum('number_of_passengers');
                            }),
                        Infolists\Components\TextEntry::make('total_revenue_usd')
                            ->label('Total Revenue (USD)')
                            ->state(function ($record) {
                                return $record->tripPassengers->sum('total_usd');
                            })
                            ->money('USD'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Fuel Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('petrol_consumed')
                            ->label('Petrol Consumed (liters)'),
                        Infolists\Components\TextEntry::make('petrol_filled')
                            ->label('Petrol Filled (liters)'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Remarks')
                    ->schema([
                        Infolists\Components\TextEntry::make('remarks')
                            ->markdown()
                            ->columnSpanFull(),
                    ]),
                    
            ]);
    }
}