<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripResource\Pages;
use App\Filament\Resources\TripResource\RelationManagers;
use App\Models\Trip;
use App\Models\Hotel;
use App\Models\TripType;
use App\Models\Boat;
use App\Models\User;
use App\Models\Invoices;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class TripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-americas';

    /**
     * Send invoice email to hotel contact person
     *
     * @param Hotel $hotel
     * @param Invoices $invoice
     * @param Trip $trip
     * @return void
     */

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Trip Information')
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->required(),
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
                                    ->default('cash'),
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
            ->defaultSort('date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
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
                Tables\Actions\Action::make('complete_trip')
                ->label('Complete Trip')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function (Trip $record) {
                    if ($record->status === 'scheduled') {
                        // Log the action
                        \Illuminate\Support\Facades\Log::info("Starting trip completion process for trip ID: {$record->id}");
                        
                        // Update trip status
                        $record->update(['status' => 'completed']);
                        
                        // Generate invoices for each hotel in the trip
                        $tripPassengersByHotel = $record->tripPassengers()->get()->groupBy('hotel_id');
                        $invoiceCount = 0;
                        $ticketCount = 0;
                        
                        // Calculate issue date from trip date
                        $issueDate = $record->date;
                        
                        // Calculate due date (1 week after issue date)
                        $dueDate = date('Y-m-d', strtotime($issueDate . ' + 7 days'));
                        
                        foreach ($tripPassengersByHotel as $hotelId => $passengers) {
                            if (!$hotelId) continue; // Skip if hotel_id is null
                            
                            // Log hotel processing
                            \Illuminate\Support\Facades\Log::info("Processing hotel ID: {$hotelId} with " . count($passengers) . " passenger records");
                            
                            // Calculate total amount for this hotel
                            $totalAmount = $passengers->sum(function ($passenger) {
                                $passengerCount = $passenger->number_of_passengers;
                                $perPassengerCharge = ($passenger->excursion_charge + $passenger->boat_charge);
                                return ($perPassengerCharge * $passengerCount) + $passenger->charter_charge;
                            });
                            
                            
                            // Generate invoice number
                            $lastInvoice = Invoices::orderBy('id', 'desc')->first();
                            $lastNumber = $lastInvoice ? intval(substr($lastInvoice->invoice_number, 8, 3)) : 0;
                            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
                            $invoiceNumber = 'AK/' . date('Y') . '/' . $newNumber;
                            
                            \Illuminate\Support\Facades\Log::info("Generated invoice number: {$invoiceNumber}");
                            
                            // Create invoice record with trip_id, issue_date, and due_date
                            $invoice = Invoices::create([
                                'invoice_number' => $invoiceNumber,
                                'hotel_id' => $hotelId,
                                'trip_id' => $record->id,  // Include trip_id
                                'month' => date('F'),      // Full month name
                                'year' => date('Y'),
                                'issue_date' => $issueDate, // Set to trip date
                                'due_date' => $dueDate,     // Set to 1 week after issue date
                                'total_amount' => $totalAmount,
                                'status' => 'draft',
                            ]);
                            
                            \Illuminate\Support\Facades\Log::info("Created invoice ID: {$invoice->id} for hotel {$hotelId}");
                            $invoiceCount++;
                            
                            
                            // Create tickets for each passenger entry
                            foreach ($passengers as $passenger) {
                                // Create a ticket record
                                \App\Models\Ticket::create([
                                    'invoice_id' => $invoice->id,
                                    'trip_id' => $record->id,
                                    'passenger_id' => $passenger->id,
                                    'is_hotel_ticket' => true,
                                ]);
                                $ticketCount++;
                                
                                \Illuminate\Support\Facades\Log::info("Created ticket for passenger ID: {$passenger->id} with {$passenger->number_of_passengers} passengers");
                            }
                        }
                        
                        // Show success notification
                        Notification::make()
                            ->success()
                            ->title('Trip Completed Successfully')
                            ->body("Trip has been marked as completed. {$invoiceCount} invoice(s) and {$ticketCount} ticket(s) have been generated.")
                            ->persistent()
                            ->send();
                            
                        \Illuminate\Support\Facades\Log::info("Trip completion process successful for trip {$record->id}. Generated {$invoiceCount} invoices and {$ticketCount} tickets");
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Complete Trip')
                ->modalDescription('Are you sure you want to mark this trip as completed? This will create invoice records for each hotel and generate tickets.')
                ->modalSubmitActionLabel('Yes, complete trip')
                ->visible(fn (Trip $record) => $record->status === 'scheduled'),


                Tables\Actions\Action::make('view_tickets')
                    ->label('View Tickets')
                    ->icon('heroicon-o-ticket')
                    ->color('warning')
                    ->modalHeading(fn (Trip $record) => "Ticket Details for Trip")
                    ->modalContent(function (Trip $record) {
                        $tickets = $record->tripPassengers()->with('hotel')->get()->map(function ($passenger) {
                            return [
                                'hotel' => $passenger->hotel->name ?? 'Walk-in',
                                'passengers' => $passenger->number_of_passengers,
                                'excursion_charge' => $passenger->excursion_charge,
                                'boat_charge' => $passenger->boat_charge,
                                'charter_charge' => $passenger->charter_charge,
                                'total_usd' => $passenger->total_usd,
                            ];
                        });
                        
                        return view('filament.modals.trip-tickets', [
                            'tickets' => $tickets,
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
                    // Add bulk complete trips action
                    Tables\Actions\BulkAction::make('complete_trips')
                        ->label('Complete Trips')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $completedCount = 0;
                            $totalInvoices = 0;
                            $totalTickets = 0;
                            
                            foreach ($records as $record) {
                                if ($record->status === 'scheduled') {
                                    // Update trip status
                                    $record->update(['status' => 'completed']);
                                    $completedCount++;
                                    
                                    // Generate invoices for each hotel in the trip
                                    $tripPassengersByHotel = $record->tripPassengers()->get()->groupBy('hotel_id');
                                    
                                    // Calculate issue date from trip date
                                    $issueDate = $record->date;
                                    
                                    // Calculate due date (1 week after issue date)
                                    $dueDate = date('Y-m-d', strtotime($issueDate . ' + 7 days'));
                                    
                                    foreach ($tripPassengersByHotel as $hotelId => $passengers) {
                                        if (!$hotelId) continue; // Skip if hotel_id is null
                                        
                                        // Calculate total amount for this hotel
                                        $totalAmount = $passengers->sum(function ($passenger) {
                                            $passengerCount = $passenger->number_of_passengers;
                                            $perPassengerCharge = ($passenger->excursion_charge + $passenger->boat_charge);
                                            return ($perPassengerCharge * $passengerCount) + $passenger->charter_charge;
                                        });
                                        
                                        // Generate invoice number
                                        $lastInvoice = Invoices::orderBy('id', 'desc')->first();
                                        $lastNumber = $lastInvoice ? intval(substr($lastInvoice->invoice_number, 4, 4)) : 0;
                                        $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                                        $invoiceNumber = 'INV/' . $newNumber . '/' . date('Y');
                                        
                                        // Create invoice record
                                        $invoice = Invoices::create([
                                            'invoice_number' => $invoiceNumber,
                                            'hotel_id' => $hotelId,
                                            'trip_id' => $record->id,
                                            'month' => date('F'), // Full month name
                                            'year' => date('Y'),
                                            'issue_date' => $issueDate,
                                            'due_date' => $dueDate,
                                            'total_amount' => $totalAmount,
                                            'status' => 'draft',
                                        ]);
                                        
                                        $totalInvoices++;

                                        // Create tickets for each passenger entry
                                        foreach ($passengers as $passenger) {
                                            // Create a ticket record
                                            \App\Models\Ticket::create([
                                                'invoice_id' => $invoice->id,
                                                'trip_id' => $record->id,
                                                'passenger_id' => $passenger->id,
                                                'is_hotel_ticket' => true,
                                            ]);
                                            $totalTickets++;
                                        }
                                    }
                                }
                            }
                            
                            // Show success notification
                            if ($completedCount > 0) {
                                Notification::make()
                                    ->success()
                                    ->title('Trips Completed Successfully')
                                    ->body("{$completedCount} trip(s) have been marked as completed. {$totalInvoices} invoice(s) and {$totalTickets} ticket(s) have been generated.")
                                    ->persistent()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Complete Selected Trips')
                        ->modalDescription('Are you sure you want to mark all selected trips as completed? This will create invoice records for each hotel and generate tickets.')
                        ->modalSubmitActionLabel('Yes, complete trips')
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => true),
                    
                    
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