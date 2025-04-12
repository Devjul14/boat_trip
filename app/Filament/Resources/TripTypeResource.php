<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripTypeResource\Pages;
use App\Filament\Resources\TripTypeResource\RelationManagers;
use App\Models\TripType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TripTypeResource extends Resource
{
    protected static ?string $model = TripType::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('default_excursion_charge')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('default_boat_charge')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('default_charter_charge')
                    ->required()
                    ->numeric()
                    ->default(0.00),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('default_excursion_charge')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('default_boat_charge')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('default_charter_charge')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListTripTypes::route('/'),
            'create' => Pages\CreateTripType::route('/create'),
            'edit' => Pages\EditTripType::route('/{record}/edit'),
        ];
    }
}
