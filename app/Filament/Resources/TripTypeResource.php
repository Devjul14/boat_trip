<?php

namespace App\Filament\Resources;

use App\Models\TripType;
use App\Models\ExpenseType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use App\Filament\Resources\TripTypeResource\Pages;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;


class TripTypeResource extends Resource
{
    protected static ?string $model = TripType::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Master Data';


public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(100),
            Forms\Components\Textarea::make('description')
                ->maxLength(65535),
            Forms\Components\Toggle::make('active')
                ->default(true),

            Forms\Components\Section::make('Default Charges')
                ->description('Set the default charges for each expense type')
                ->schema(function ($record) {
                    $expenseTypes = \App\Models\ExpenseType::where('active', true)->get();
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
                            ->reactive()
                            ->afterStateUpdated(function ($state) use ($expenseType) {
                                Log::info("Charge updated for {$expenseType->name}: {$state}");
                            });
                    }

                    return $fields;
                })->columns(2),
        ]);
}

public static function mutateFormDataBeforeSave(array $data): array
{
    if (isset($data['charges'])) {
        Log::info('Charges data before save:', $data['charges']);
        session()->put('trip_type_charges', $data['charges']);
        unset($data['charges']);
    } else {
        Log::info('No charges data found in form submission.');
    }
    return $data;
}

public static function afterSave($record): void
{
    $charges = session()->pull('trip_type_charges', []);
    Log::info('Processing charges in afterSave:', $charges);

    if (!empty($charges)) {
        foreach ($charges as $expenseTypeId => $charge) {
            $expenseTypeId = (int)$expenseTypeId;
            $chargeValue = floatval($charge);
            
            Log::info("Updating expense type {$expenseTypeId} with charge {$chargeValue}");
            
            DB::table('expense_type_trip_types')
                ->updateOrInsert(
                    [
                        'trip_type_id' => $record->id,
                        'expense_type_id' => $expenseTypeId,
                        'is_master' => true,
                        'trip_id' => null,
                    ],
                    [
                        'default_charge' => $chargeValue,
                        'updated_at' => now(),
                    ]
                );
            
            Log::info("Updated record for expense type {$expenseTypeId}");
        }
        
        $record->refresh();
        
        $finalCharges = DB::table('expense_type_trip_types')
            ->where('trip_type_id', $record->id)
            ->where('is_master', true)
            ->whereNull('trip_id')
            ->get(['expense_type_id', 'default_charge']);
        
        Log::info('Final state after save:', json_decode(json_encode($finalCharges), true));
    } else {
        Log::info('No charges to process.');
    }
}

 

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\IconColumn::make('active')
                    ->boolean(),

                
                Tables\Columns\Layout\Panel::make([
                    Tables\Columns\TextColumn::make('expenseTypes')
                        ->label('Expense Types')
                        ->formatStateUsing(function ($record) {
                            // Intentionally use $record instead of $state
                            $expenseTypes = $record->expenseTypes;
                            
                            if ($expenseTypes && $expenseTypes->count() > 0) {
                                $html = '<div class="grid grid-cols-2 gap-2">';
                                foreach ($expenseTypes as $expenseType) {
                                    $charge = $expenseType->pivot->default_charge ?? 0;
                                    $html .= "<div><span class='font-bold'>{$expenseType->name}:</span> $" .
                                        number_format($charge, 2) . "</div>";
                                }
                                $html .= '</div>';
                                return new HtmlString($html);
                            }
                            return 'No expense types found.';
                        })
                        ->html(),
                ])
                ->collapsible(),

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
                Tables\Filters\SelectFilter::make('active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTripTypes::route('/'),
            'create' => Pages\CreateTripType::route('/create'),
            'edit' => Pages\EditTripType::route('/{record}/edit'),
        ];
    }
}