<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Filament\Resources\TicketResource\RelationManagers;
use App\Models\Trip;
use App\Models\Ticket;
use App\Models\Hotel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TicketResource\Api\Handlers\SearchHandler;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public $trip_id = null;

    public function mount()
    {
        // Jika data edit, pastikan $this->trip_id sudah ada nilainya
        $this->form->fill([
            'trip_id' => $this->trip_id,
            // properti lain juga bisa diisi di sini
        ]);
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('trip_id')
                    ->label('Trip')
                    ->options(
                        Trip::with('tripType')
                            ->where('status', 'scheduled')
                            ->get()
                            ->mapWithKeys(fn ($trip) => [
                                $trip->id => $trip->date->format('d F y') . ' - ' . $trip->tripType->name
                            ])
                    )
                    ->required()
                    ->searchable()
                    ->dehydrated(true)
                    ->live()
                    ->default(fn ($get) => $get('trip_id')) // Menyimpan nilai trip_id
                    ->afterStateUpdated(function ($state, callable $set, $livewire) {
                        if ($state) {
                            // Pastikan trip_id tetap tersimpan di state dan perbarui nilai lainnya sesuai dengan trip yang dipilih
                            $livewire->loadExpenses($state);
                        } else {
                            $set('expense_amounts', []); // Kosongkan expense_amounts jika trip_id dihapus
                        }
                    }),

                Forms\Components\Select::make('hotel_id')
                    ->label('Hotel')
                    ->options(Hotel::pluck('name', 'id'))
                    ->searchable(),

                Forms\Components\TextInput::make('number_of_passengers')
                    ->label('Number of Passengers')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->minValue(1),

                Forms\Components\Select::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                    ])
                    ->required()
                    ->default('pending'),

                Forms\Components\Select::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'credit_card' => 'Credit Card',
                    ])
                    ->required()
                    ->default('cash'),

                

                Forms\Components\Section::make('Expense Details')
                    ->schema([
                        Forms\Components\Repeater::make('expense_amounts')
                            ->schema([
                                Forms\Components\Hidden::make('expense_id'),
                                Forms\Components\TextInput::make('expense_name')
                                    ->label('Expense Name')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('amount')
                                    ->label('Amount')
                                    ->numeric()
                                    ->required(),
                            ])
                            ->disableItemCreation()
                            ->disableItemDeletion()
                            ->columns(2)
                            ->columnSpanFull()
                    ])
            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('trip.date')
                    ->date('d F Y'),
                Tables\Columns\TextColumn::make('trip.tripType.name')
                    ->numeric(),
                Tables\Columns\TextColumn::make('hotel_id')
                    ->label('Hotel')
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        return $record->hotel_id == null ? 'Walk in Trip' : $record->hotel->name;
                    }),
                Tables\Columns\TextColumn::make('number_of_passengers')
                    ->label('Total Passengers')
                    ->numeric(),
                // Tables\Columns\TextColumn::make('total_usd')
                //     ->label('Price')                    
                //     ->money('USD'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('hotel_id')
                    ->label('Hotel')
                    ->options(Hotel::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'credit_card' => 'Credit Card',
                    ]),
                Tables\Filters\Filter::make('is_hotel_ticket')
                    ->label('Hotel Tickets Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_hotel_ticket', true)),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }

   
    public static function api(): array
    {
        return [
            // handler lainnya (misal: CreateHandler, UpdateHandler)
            SearchHandler::class,
        ];
    }

}
