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

                Infolists\Components\Section::make('Hotels & Passengers')
                ->schema(function ($record) {
                    $entries = [];
                    
                    // Create entry for each ticket
                    foreach ($record->ticket as $index => $ticket) {
                        $entries[] = Infolists\Components\Grid::make([$index])
                            ->schema([
                                Infolists\Components\TextEntry::make("ticket.{$index}.hotel.name")
                                    ->label('Hotel'),
                                Infolists\Components\TextEntry::make("ticket.{$index}.number_of_passengers")
                                    ->label('Passengers'),
                                Infolists\Components\TextEntry::make("ticket.{$index}.total_rf")
                                    ->label('Total Amount ($)')
                                    ->money('USD'),
                                Infolists\Components\TextEntry::make("ticket.{$index}.payment_status")
                                    ->label('Payment Status')
                                    ->badge()
                                    ->color(fn (string $state): string => 
                                        match ($state) {
                                            'pending' => 'danger',
                                            'paid' => 'success',
                                            default => 'gray',
                                        }
                                    ),
                                Infolists\Components\TextEntry::make("ticket.{$index}.payment_method")
                                    ->label('Payment Method'),
                                Infolists\Components\IconEntry::make("ticket.{$index}.is_hotel_ticket")
                                    ->label('Hotel Ticket')
                                    ->boolean(),
                            ])
                            ->columns(3);
                    }
                    
                    if (empty($entries)) {
                        $entries[] = Infolists\Components\TextEntry::make('no_passengers')
                            ->label('')
                            ->state('No tickets recorded for this trip.')
                            ->columnSpanFull();
                    }
                    
                    return $entries;
                }),

                Infolists\Components\Section::make('Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('hotels_count')
                            ->label('Total Hotels')
                            ->state(function ($record) {
                                return $record->ticket->count();
                            }),
                        Infolists\Components\TextEntry::make('total_passengers')
                            ->label('Total Passengers')
                            ->state(function ($record) {
                                return $record->ticket->sum('number_of_passengers');
                            }),
                        Infolists\Components\TextEntry::make('total_revenue')
                            ->label('Total Revenue (USD)')
                            ->state(function ($record) {
                                return $record->ticket->sum('total_rf');
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