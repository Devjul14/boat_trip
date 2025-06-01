<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    public function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Trip Information')
                    ->schema([
                        TextEntry::make('trip.date')
                            ->label('Date')
                            ->date('d F Y'),
                        TextEntry::make('trip.bill_number')
                            ->label('Bill Number'),
                        TextEntry::make('trip.tripType.name')
                            ->label('Trip Type'),
                        TextEntry::make('trip.boat.name')
                            ->label('Boat'),
                        TextEntry::make('trip.boatman.name')
                            ->label('Boatman'),
                        TextEntry::make('trip.status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'cancelled' => 'danger',
                                'scheduled' => 'warning',
                                'completed' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('trip.remarks')
                            ->label('Remarks')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Ticket Information')
                    ->schema([
                        TextEntry::make('hotel.name')
                            ->label('Hotel')
                            ->getStateUsing(fn ($record) => $record->hotel_id === null ? 'Walk in Trip' : $record->hotel->name),
                        TextEntry::make('number_of_passengers')
                            ->label('Total Passengers'),
                        TextEntry::make('payment_status')
                            ->label('Payment Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'paid' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('payment_method')
                            ->label('Payment Method')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'cash' => 'success',
                                'bank_transfer' => 'success',
                                'credit_card' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Created At'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->label('Updated At'),
                    ])
                    ->columns(2),
                
                Section::make('Expenses')
                    ->schema([
                        RepeatableEntry::make('ticketExpenses')
                            ->label('Expenses List')
                            ->schema([
                                TextEntry::make('expense.name')
                                    ->label('Expense Name'),
                                TextEntry::make('amount')
                                    ->money('USD')
                                    ->label('Amount'),
                            ])
                            ->columns(2),
                        
                        // Add the total amount field here
                        TextEntry::make('total_amount')
                            ->label('Total Amount')
                            ->money('USD')
                            ->weight('bold')
                            ->size('lg')
                            ->color('success')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->ticketExpenses->isNotEmpty())
            ]);
    }
}