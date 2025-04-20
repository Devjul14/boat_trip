<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripPassengersResource\Pages;
use App\Models\TripPassengers;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class TripPassengersResource extends Resource
{
    protected static ?string $model = TripPassengers::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getModelLabel(): string
    {
        return 'Tickets'; 
    }
    
    
    public static function getNavigationLabel(): string
    {
        return 'Tiket';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('trip.date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('trip.date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('trip.bill_number')
                //     ->sortable()
                //     ->searchable(),
                Tables\Columns\TextColumn::make('hotel.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('number_of_passengers')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('excursion_charge')
                    ->numeric()
                    ->sortable()
                    ->money('USD'),
                Tables\Columns\TextColumn::make('boat_charge')
                    ->numeric()
                    ->sortable()
                    ->money('USD'),
                Tables\Columns\TextColumn::make('charter_charge')
                    ->numeric()
                    ->sortable()
                    ->money('USD'),
                Tables\Columns\TextColumn::make('total_usd')
                    ->numeric()
                    ->state(function ($record): float {
                        return $record->excursion_charge + $record->boat_charge + $record->charter_charge;
                    })
                    ->sortable()
                    ->money('USD'),
                Tables\Columns\TextColumn::make('total_rf')
                    ->numeric()
                    ->sortable()
                    ->money('MVR'),
                Tables\Columns\TextColumn::make('payment_status'),
                Tables\Columns\TextColumn::make('payment_method')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('hotel_id')
                    ->label('Hotel')
                    ->relationship('hotel', 'name')
                    ->searchable()
                    ->preload()
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTripPassengers::route('/'),
        ];
    }
}