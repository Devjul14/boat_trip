<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripTypeResource\Pages;
use App\Filament\Resources\TripTypeResource\RelationManagers;
use App\Models\TripType;
use App\Models\ExpenseType;
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

class TripTypeResource extends Resource
{
    protected static ?string $model = TripType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
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
                Forms\Components\Section::make('Default Charges')
                    ->description('Set the default charges for each expense type')
                    ->schema(function (TripType $record = null) {
                        $expenseTypes = ExpenseType::where('active', true)->get();
                        $fields = [];

                        foreach ($expenseTypes as $expenseType) {
                            $fields[] = Forms\Components\TextInput::make("charges.{$expenseType->id}")
                                ->label($expenseType->name)
                                ->prefix('$')
                                ->numeric()
                                ->default(0)
                                ->step(0.01)
                                ->hint("Default {$expenseType->name} charge")
                                ->afterStateHydrated(function ($state, $component) use ($record, $expenseType) {
                                    if ($record && $record->exists) {
                                        $pivotValue = DB::table('expense_type_trip_types')
                                            ->where('trip_type_id', $record->id)
                                            ->where('expense_type_id', $expenseType->id)
                                            ->where('is_master', true)
                                            ->whereNull('trip_id')
                                            ->value('default_charge');

                                        if ($pivotValue !== null) {
                                            $component->state($pivotValue);
                                            Log::info("Loaded charge for {$expenseType->name}: {$pivotValue}");
                                        }
                                    }
                                })
                                ->reactive();
                        }

                        return $fields;
                    })->columns(2),
            ]);
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