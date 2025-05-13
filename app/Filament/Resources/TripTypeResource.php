<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripTypeResource\Pages;
use App\Filament\Resources\TripTypeResource\RelationManagers;
use App\Models\TripType;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;

class TripTypeResource extends Resource
{
    protected static ?string $model = TripType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        \Log::info('Expense:', Expense::all()->toArray());
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('active')
                    ->required(),

                FileUpload::make('image')
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        '16:9',
                        '4:3',
                        '1:1',
                    ]),

                Select::make('expenses')
                ->label('Choose Expense')
                ->multiple()
                ->relationship('expenses', 'name')
                ->preload()
                ->required(),
            ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Remove expenses from the data before creating the model
        $expenseIds = $data['expenses'] ?? [];
        unset($data['expenses']);
        
        // Create the trip type
        $tripType = static::getModel()::create($data);
        
        // Sync the expenses
        $tripType->expenses()->sync($expenseIds);
        
        return $tripType;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Remove expenses from the data before updating the model
        $expenseIds = $data['expenses'] ?? [];
        unset($data['expenses']);
        
        // Update the trip type
        $record->update($data);
        
        // Sync the expenses
        $record->expenses()->sync($expenseIds);
        
        return $record;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\IconColumn::make('active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
                ImageColumn::make('image')
                    ->label('Image')
                    ->url(fn ($record) => $record->image ? asset('storage/' . $record->image) : asset('storage/placeholder.png')),

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