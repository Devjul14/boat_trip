<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripPassengersResource\Pages;
use App\Filament\Resources\TripPassengersResource\RelationManagers;
use App\Models\TripPassengers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TripPassengersResource extends Resource
{
    protected static ?string $model = TripPassengers::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('trip_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('hotel_id')
                    ->tel()
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('number_of_passengers')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('excursion_charge')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('boat_charge')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('charter_charge')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('total_usd')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('total_rf')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('payment_status')
                    ->required(),
                Forms\Components\TextInput::make('payment_method')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('trip_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hotel_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('number_of_passengers')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('excursion_charge')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('boat_charge')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('charter_charge')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_usd')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_rf')
                    ->numeric()
                    ->sortable(),
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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTripPassengers::route('/'),
            'create' => Pages\CreateTripPassengers::route('/create'),
            'edit' => Pages\EditTripPassengers::route('/{record}/edit'),
        ];
    }
}
