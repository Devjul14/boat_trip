<?php

namespace App\Filament\Resources\TripResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Hotel;
use Filament\Forms\Get;
use Filament\Forms\Set;

class TripPassengersRelationManager extends RelationManager
{
    protected static string $relationship = 'tripPassengers';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('hotel_id')
                    ->label('Hotel')
                    ->options(Hotel::pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                
                Forms\Components\TextInput::make('number_of_passengers')
                    ->label('Number of Passengers')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->reactive(),
                
                Forms\Components\TextInput::make('excursion_charge')
                    ->label('Excursion Charge ($)')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->reactive(),
                
                Forms\Components\TextInput::make('boat_charge')
                    ->label('Boat Charge ($)')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->reactive(),
                
                Forms\Components\TextInput::make('charter_charge')
                    ->label('Charter Charge ($)')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->reactive(),
                
                Forms\Components\TextInput::make('total_usd')
                    ->label('Total (USD)')
                    ->required()
                    ->numeric()
                    ->reactive()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        // Auto calculate total based on inputs
                        $passengers = (int) $get('number_of_passengers');
                        $excursion = (float) $get('excursion_charge');
                        $boat = (float) $get('boat_charge');
                        $charter = (float) $get('charter_charge');
                        
                        $total = ($excursion + $boat) * $passengers + $charter;
                        $set('total_usd', $total);
                    }),
                
                Forms\Components\TextInput::make('total_rf')
                    ->label('Total (RF)')
                    ->numeric()
                    ->default(0),
                
                Forms\Components\Select::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                    ])
                    ->default('pending')
                    ->reactive(),
                
                Forms\Components\Select::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'credit_card' => 'Credit Card',
                    ])
                    ->visible(fn (Get $get): bool => $get('payment_status') === 'paid'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hotel.name')
                    ->label('Hotel')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('number_of_passengers')
                    ->label('Passengers')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('excursion_charge')
                    ->label('Excursion ($)')
                    ->money('USD')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('boat_charge')
                    ->label('Boat ($)')
                    ->money('USD')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('charter_charge')
                    ->label('Charter ($)')
                    ->money('USD')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('total_usd')
                    ->label('Total (USD)')
                    ->money('USD')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('total_rf')
                    ->label('Total (RF)')
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->colors([
                        'danger' => 'pending',
                        'success' => 'paid',
                    ]),
                
                Tables\Columns\TextColumn::make('payment_method')
                    ->formatStateUsing(fn ($state) => $state ? ucwords(str_replace('_', ' ', $state)) : '-'),
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $passengers = (int) $data['number_of_passengers'];
                        $excursion = (float) $data['excursion_charge'];
                        $boat = (float) $data['boat_charge'];
                        $charter = (float) $data['charter_charge'];
                        
                        $data['total_usd'] = ($excursion + $boat) * $passengers + $charter;
                        
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $passengers = (int) $data['number_of_passengers'];
                        $excursion = (float) $data['excursion_charge'];
                        $boat = (float) $data['boat_charge'];
                        $charter = (float) $data['charter_charge'];
                        
                        $data['total_usd'] = ($excursion + $boat) * $passengers + $charter;
                        
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('updatePaymentStatus')
                        ->label('Update Payment Status')
                        ->icon('heroicon-o-credit-card')
                        ->form([
                            Forms\Components\Select::make('payment_status')
                                ->label('Payment Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'paid' => 'Paid',
                                ])
                                ->required(),
                                
                            Forms\Components\Select::make('payment_method')
                                ->label('Payment Method')
                                ->options([
                                    'cash' => 'Cash',
                                    'bank_transfer' => 'Bank Transfer',
                                    'credit_card' => 'Credit Card',
                                ])
                                ->visible(fn (Get $get): bool => $get('payment_status') === 'paid')
                                ->required(fn (Get $get): bool => $get('payment_status') === 'paid'),
                        ])
                        ->action(function (array $data, \Illuminate\Database\Eloquent\Collection $records) {
                            foreach ($records as $record) {
                                $update = [
                                    'payment_status' => $data['payment_status'],
                                ];
                                
                                if ($data['payment_status'] === 'paid' && isset($data['payment_method'])) {
                                    $update['payment_method'] = $data['payment_method'];
                                }
                                
                                $record->update($update);
                            }
                        }),
                ]),
            ])
            ->defaultSort('hotel.name');
    }
}