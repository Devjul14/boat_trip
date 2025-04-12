<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceItemsResource\Pages;
use App\Filament\Resources\InvoiceItemsResource\RelationManagers;
use App\Models\InvoiceItems;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceItemsResource extends Resource
{
    protected static ?string $model = InvoiceItems::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function shouldRegisterNavigation(): bool
    {
        // Return false to hide this resource from the navigation
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('invoice_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('trip_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255),
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
                Forms\Components\TextInput::make('total_amount')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('trip_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
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
                Tables\Columns\TextColumn::make('total_amount')
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
            'index' => Pages\ListInvoiceItems::route('/'),
            'create' => Pages\CreateInvoiceItems::route('/create'),
            'edit' => Pages\EditInvoiceItems::route('/{record}/edit'),
        ];
    }
}
