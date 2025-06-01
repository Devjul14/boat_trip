<?php

namespace App\Filament\Resources\InvoicesResource\Pages;

use App\Filament\Resources\InvoicesResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\BadgeEntry;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoicesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => in_array($record->status, ['draft', 'sent'])),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Invoice Information')
                    ->schema([
                        TextEntry::make('invoice_number'),
                        TextEntry::make('month'),
                        TextEntry::make('year'),
                        TextEntry::make('issue_date')->date(),
                        TextEntry::make('due_date')->date(),
                        TextEntry::make('total_amount')->money('USD'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft', 'overdue' => 'warning',
                                'paid' => 'success',
                                'sent' => 'primary',
                                default => 'gray',
                            })
                    ])->columns(3),

                Section::make('Hotel Information')
                    ->schema([
                        TextEntry::make('hotel.name')->label('Hotel Name'),
                        TextEntry::make('hotel.contact_person')->label('Contact Person'),
                        TextEntry::make('hotel.email')->label('Email'),
                        TextEntry::make('hotel.phone')->label('Phone'),
                        TextEntry::make('hotel.address')->label('Address'),
                    ])->columns(3),

                Section::make('Trip Details')
                    ->schema([
                        TextEntry::make('trip.date')->date(),
                        TextEntry::make('trip.tripType.name')->label('Trip Type'),
                        TextEntry::make('trip.boat.name')->label('Boat'),
                        TextEntry::make('trip.boatman.name')->label('Boatman'),
                        TextEntry::make('trip.status')->badge()->color(fn (string $state): string => match ($state) {
                            'cancelled' => 'danger',
                            'scheduled' => 'warning',
                            'completed' => 'success',
                            default => 'gray',
                        }),
                        TextEntry::make('trip.remarks')->markdown()->columnSpanFull()
                    ])->columns(3),

                // Section::make('Tickets')
                //     ->schema([
                //         RepeatableEntry::make('trip.ticket')
                //             ->label('Tickets')
                //             ->schema([
                //                 TextEntry::make('hotel.name')->label('Hotel'),
                //                 TextEntry::make('number_of_passengers'),
                //                 TextEntry::make('price')->money('USD'),
                //                 TextEntry::make('total_usd')->money('USD'),
                //                 TextEntry::make('payment_method'),
                //                 TextEntry::make('payment_status'),
                //                 TextEntry::make('total_amount')->label('Calculated Total')->money('USD'),
                //                 RepeatableEntry::make('ticketExpenses')
                //                     ->label('Expenses')
                //                     ->schema([
                //                         TextEntry::make('expense.name')->label('Expense Name'),
                //                         TextEntry::make('amount')->money('USD'),
                //                     ])->columns(2),
                //             ])->columns(3)
                //             ->visible(fn ($record) => $record->trip?->ticket?->isNotEmpty()),
                //     ])
            ]);
    }
}
