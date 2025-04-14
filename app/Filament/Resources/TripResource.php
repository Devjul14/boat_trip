<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripResource\Pages;
use App\Filament\Resources\TripResource\RelationManagers;
use App\Models\Trip;
use App\Models\Hotel;
use App\Models\TripType;
use App\Models\Boat;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-americas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Trip Information')
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->required(),
                        Forms\Components\TextInput::make('bill_number')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('trip_type_id')
                            ->label('Trip Type')
                            ->options(TripType::pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $tripType = TripType::find($state);
                                    if ($tripType) {
                                        $set('default_excursion_charge', $tripType->default_excursion_charge);
                                        $set('default_boat_charge', $tripType->default_boat_charge);
                                        $set('default_charter_charge', $tripType->default_charter_charge);
                                    }
                                }
                            }),
                        Forms\Components\Hidden::make('default_excursion_charge'),
                        Forms\Components\Hidden::make('default_boat_charge'),
                        Forms\Components\Hidden::make('default_charter_charge'),
                        Forms\Components\Select::make('boat_id')
                            ->label('Boat')
                            ->options(Boat::pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('boatman_id')
                            ->label('Boatman')
                            ->options(function() {
                                return User::whereHas('roles', function ($query) {
                                        $query->where('name', 'boatman');
                                    })
                                    ->orWhere('role', 'boatman')
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'scheduled' => 'Scheduled',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('scheduled'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Hotels & Passengers')
                    ->schema([
                        Forms\Components\Repeater::make('hotels')
                            ->relationship('tripPassengers')
                            ->schema([
                                Forms\Components\Select::make('hotel_id')
                                    ->label('Hotel')
                                    ->options(Hotel::pluck('name', 'id'))
                                    ->required()
                                    ->searchable(),
                                Forms\Components\TextInput::make('number_of_passengers')
                                    ->label('Number of Passengers')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1)
                                    ->reactive(),
                                Forms\Components\TextInput::make('excursion_charge')
                                    ->label('Excursion Charge ($)')
                                    ->numeric()
                                    ->default(function (Get $get) {
                                        return $get('../../default_excursion_charge');
                                    }),
                                Forms\Components\TextInput::make('boat_charge')
                                    ->label('Boat Charge ($)')
                                    ->numeric()
                                    ->default(function (Get $get) {
                                        return $get('../../default_boat_charge');
                                    }),
                                Forms\Components\TextInput::make('charter_charge')
                                    ->label('Charter Charge ($)')
                                    ->numeric()
                                    ->default(function (Get $get) {
                                        return $get('../../default_charter_charge');
                                    }),
                                Forms\Components\TextInput::make('total_usd')
                                    ->label('Total (USD)')
                                    ->numeric()
                                    ->reactive()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        // Kalkulator Total USD berdasarkan charges dan jumlah penumpang
                                        $passengers = (int) $get('number_of_passengers');
                                        $excursion = (float) $get('excursion_charge');
                                        $boat = (float) $get('boat_charge');
                                        $charter = (float) $get('charter_charge');
                                        
                                        $total = ($excursion + $boat) * $passengers + $charter;
                                        $set('total_usd', $total);
                                    })
                                    ->disabled(),
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
                                    ->default('pending'),
                                Forms\Components\Select::make('payment_method')
                                    ->label('Payment Method')
                                    ->options([
                                        'cash' => 'Cash',
                                        'bank_transfer' => 'Bank Transfer',
                                        'credit_card' => 'Credit Card',
                                    ])
                                    ->nullable()
                                    ->visible(fn (Get $get): bool => $get('payment_status') === 'paid'),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->createItemButtonLabel('Add Hotel'),
                    ]),
                
                Forms\Components\Section::make('Fuel & Remarks')
                    ->schema([
                        Forms\Components\TextInput::make('petrol_consumed')
                            ->label('Petrol Consumed (liters)')
                            ->numeric()
                            ->default(0.00),
                        Forms\Components\TextInput::make('petrol_filled')
                            ->label('Petrol Filled (liters)')
                            ->numeric()
                            ->default(0.00),
                        Forms\Components\Textarea::make('remarks')
                            ->columnSpan(2),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('bill_number')
                //     ->searchable(),
                Tables\Columns\TextColumn::make('tripType.name')
                    ->label('Trip Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('boat.name')
                    ->label('Boat')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('boatman.name')
                    ->label('Boatman')
                    ->searchable()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('tripPassengers_count')
                //     ->label('Total Hotels')
                //     ->counts('tripPassengers')
                //     ->sortable(),
                Tables\Columns\TextColumn::make('total_passengers')
                    ->label('Total Passengers')
                    ->getStateUsing(function (Trip $record) {
                        return $record->tripPassengers->sum('number_of_passengers');
                    }),
                // Tables\Columns\TextColumn::make('total_revenue_usd')
                //     ->label('Revenue (USD)')
                //     ->getStateUsing(function (Trip $record) {
                //         return $record->tripPassengers->sum('total_usd');
                //     })
                //     ->money('USD'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'cancelled',
                        'warning' => 'scheduled',
                        'success' => 'completed',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('trip_type_id')
                    ->label('Trip Type')
                    ->options(TripType::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('boat_id')
                    ->label('Boat')
                    ->options(Boat::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('boatman_id')
                    ->label('Boatman')
                    ->options(User::whereHas('roles', function ($query) {
                        $query->where('name', 'boatman');
                    })
                    ->orWhere('role', 'boatman')
                    ->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('has_hotels')
                    ->label('Has Hotel Passengers')
                    ->query(fn (Builder $query): Builder => $query->has('tripPassengers')),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_hotels')
                    ->label('View Hotels')
                    ->icon('heroicon-o-building-office')
                    ->modalHeading(fn (Trip $record) => "Hotels & Passengers for Trip {$record->bill_number}")
                    ->modalContent(function (Trip $record) {
                        $hotels = $record->tripPassengers()->with('hotel')->get()->map(function ($passenger) {
                            return [
                                'hotel' => $passenger->hotel->name ?? 'Unknown Hotel',
                                'passengers' => $passenger->number_of_passengers,
                                'total_usd' => $passenger->total_usd,
                                'payment_status' => $passenger->payment_status,
                            ];
                        });
                        
                        return view('filament.modals.trip-hotels', [
                            'hotels' => $hotels,
                            'trip' => $record,
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('change_status')
                        ->label('Change Status')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'scheduled' => 'Scheduled',
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TripPassengersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrips::route('/'),
            'create' => Pages\CreateTrip::route('/create'),
            'view' => Pages\ViewTrip::route('/{record}'),
            'edit' => Pages\EditTrip::route('/{record}/edit'),
        ];
    }
}