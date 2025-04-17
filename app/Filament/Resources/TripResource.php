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
    protected static function sendInvoiceEmail(Hotel $hotel, Invoices $invoice, Trip $trip)
    {
        $data = [
            'hotel' => $hotel,
            'invoice' => $invoice,
            'trip' => $trip,
            'contactPerson' => $hotel->contact_person
        ];
        
        try {
            Mail::send('emails.invoice', $data, function($message) use ($hotel, $invoice) {
                $message->to($hotel->email, $hotel->contact_person)
                        ->subject("New Invoice #{$invoice->invoice_number} Generated");
            });
            
            // Log successful email sending
            \Illuminate\Support\Facades\Log::info("Email sent successfully to {$hotel->email} for invoice #{$invoice->invoice_number}");
            
            return true;
        } catch (\Exception $e) {
            // Log the error but don't stop the process
            \Illuminate\Support\Facades\Log::error("Failed to send invoice email: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Check if the current user is an admin
     * 
     * @return bool
     */
    protected static function isAdmin()
    {
        // Check if user has admin role
        $user = Auth::user();
        
        // Log user information to help debug permission issues
        if ($user) {
            \Illuminate\Support\Facades\Log::info("User ID: {$user->id}, Name: {$user->name}");
            if (method_exists($user, 'getRoleNames')) {
                \Illuminate\Support\Facades\Log::info("User roles: " . implode(', ', $user->getRoleNames()->toArray()));
            } else {
                \Illuminate\Support\Facades\Log::info("User role attribute: " . ($user->role ?? 'none'));
            }
        } else {
            \Illuminate\Support\Facades\Log::warning("No authenticated user found when checking admin status");
        }
        
        // You may need to adjust this logic based on your role system
        $isAdmin = $user && (
            (method_exists($user, 'hasRole') && $user->hasRole('admin')) || 
            $user->role === 'admin' || 
            (property_exists($user, 'is_admin') && $user->is_admin)
        );
        
        \Illuminate\Support\Facades\Log::info("Admin check result: " . ($isAdmin ? 'true' : 'false'));
        
        return $isAdmin;
    }

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
                Tables\Columns\TextColumn::make('bill_number')
                    ->searchable(),
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
                                $lastNumber = $lastInvoice ? intval(substr($lastInvoice->invoice_number, 4, 4)) : 0;
                                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                                $invoiceNumber = 'INV/' . $newNumber . '/' . date('Y');
                                
                                \Illuminate\Support\Facades\Log::info("Generated invoice number: {$invoiceNumber}");
                                
                                // Get current month and year
                                $nowDate = date('Y-m-d'); // Use proper MySQL date format
                                $currentMonth = date('F'); // Full month name
                                $currentYear = date('Y');
                                
                                // Create invoice record
                                $invoice = Invoices::create([
                                    'invoice_number' => $invoiceNumber,
                                    'hotel_id' => $hotelId,
                                    'month' => $currentMonth,
                                    'year' => $currentYear,
                                    'issue_date' => null,
                                    'due_date' => null,
                                    'total_amount' => $totalAmount,
                                    'status' => 'draft',
                                ]);
                                
                                \Illuminate\Support\Facades\Log::info("Created invoice ID: {$invoice->id} for hotel {$hotelId}");
                                $invoiceCount++;
                            }
                            
                            // Show success notification
                            Notification::make()
                                ->success()
                                ->title('Trip Completed Successfully')
                                ->body("Trip {$record->bill_number} has been marked as completed and {$invoiceCount} invoice(s) have been generated.")
                                ->persistent()
                                ->send();
                                
                            \Illuminate\Support\Facades\Log::info("Trip completion process successful for trip {$record->id}. Generated {$invoiceCount} invoices");
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Complete Trip')
                    ->modalDescription('Are you sure you want to mark this trip as completed? This will create invoice records for each hotel.')
                    ->modalSubmitActionLabel('Yes, complete trip')
                    ->visible(fn (Trip $record) => $record->status === 'scheduled'),
                
                // Separate action for sending emails - admin only
                Tables\Actions\Action::make('send_invoice_emails')
                    ->label('Send Invoice')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->action(function (Trip $record) {
                        // Check if user is admin first
                        if (!self::isAdmin()) {
                            Notification::make()
                                ->danger()
                                ->title('Permission Denied')
                                ->body('Only administrators can send invoice emails.')
                                ->persistent()
                                ->send();
                            return;
                        }
                        
                        // Get invoices related to this trip (by hotels)
                        $hotelIds = $record->tripPassengers()->pluck('hotel_id')->unique()->filter();
                        $emailsSent = 0;
                        $emailsFailed = 0;
                        
                        foreach ($hotelIds as $hotelId) {
                            $hotel = Hotel::find($hotelId);
                            if (!$hotel || !$hotel->email) continue;
                            
                            // Find the most recent invoice for this hotel
                            $invoice = Invoices::where('hotel_id', $hotelId)
                                ->orderBy('created_at', 'desc')
                                ->first();
                            
                            if (!$invoice) continue;
                            
                            // Send the email
                            $result = self::sendInvoiceEmail($hotel, $invoice, $record);
                            
                            if ($result) {
                                $emailsSent++;
                            } else {
                                $emailsFailed++;
                            }
                        }
                        
                        // Show success notification
                        if ($emailsSent > 0 || $emailsFailed > 0) {
                            $notificationBody = "{$emailsSent} email(s) sent successfully";
                            
                            if ($emailsFailed > 0) {
                                $notificationBody .= ", {$emailsFailed} email(s) failed to send";
                            }
                            
                            Notification::make()
                                ->success()
                                ->title('Invoice Emails Sent')
                                ->body($notificationBody)
                                ->persistent()
                                ->send();
                        } else {
                            Notification::make()
                                ->warning()
                                ->title('No Emails Sent')
                                ->body('No valid hotels with email addresses were found for this trip.')
                                ->persistent()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Send Invoice Emails')
                    ->modalDescription('Are you sure you want to send invoice emails to all hotels in this trip?')
                    ->modalSubmitActionLabel('Yes, send emails')
                    ->visible(function (Trip $record) {
                        // Debug logs to help diagnose visibility issues
                        \Illuminate\Support\Facades\Log::info("Checking send_invoice_emails visibility for trip {$record->id}");
                        \Illuminate\Support\Facades\Log::info("Trip status: {$record->status}");
                        \Illuminate\Support\Facades\Log::info("Is admin: " . (self::isAdmin() ? 'true' : 'false'));
                        
                        // Make visible only if trip is completed and user is admin
                        return $record->status === 'completed' && self::isAdmin();
                    }),

                Tables\Actions\Action::make('view_tickets')
                    ->label('View Tickets')
                    ->icon('heroicon-o-ticket')
                    ->color('warning')
                    ->modalHeading(fn (Trip $record) => "Ticket Details for Trip {$record->bill_number}")
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
                            
                            foreach ($records as $record) {
                                if ($record->status === 'scheduled') {
                                    // Update trip status
                                    $record->update(['status' => 'completed']);
                                    $completedCount++;
                                    
                                    // Generate invoices for each hotel in the trip
                                    $tripPassengersByHotel = $record->tripPassengers()->get()->groupBy('hotel_id');
                                    
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
                                        
                                        // Get current month and year
                                        $currentMonth = date('F'); // Full month name
                                        $currentYear = date('Y');
                                        
                                        // Create invoice record
                                        $invoice = Invoices::create([
                                            'invoice_number' => $invoiceNumber,
                                            'hotel_id' => $hotelId,
                                            'month' => $currentMonth,
                                            'year' => $currentYear,
                                            'issue_date' => null,
                                            'due_date' => null,
                                            'total_amount' => $totalAmount,
                                            'status' => 'draft',
                                        ]);
                                        
                                        $totalInvoices++;
                                    }
                                }
                            }
                            
                            // Show success notification
                            if ($completedCount > 0) {
                                Notification::make()
                                    ->success()
                                    ->title('Trips Completed Successfully')
                                    ->body("{$completedCount} trip(s) have been marked as completed and {$totalInvoices} invoice(s) have been generated.")
                                    ->persistent()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Complete Selected Trips')
                        ->modalDescription('Are you sure you want to mark all selected trips as completed? This will also create invoice records for each hotel.')
                        ->modalSubmitActionLabel('Yes, complete trips')
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => true),
                    
                    // Add bulk send emails action - admin only
                    Tables\Actions\BulkAction::make('send_bulk_emails')
                        ->label('Send Invoice')
                        ->icon('heroicon-o-envelope')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            // Check if user is admin
                            if (!self::isAdmin()) {
                                Notification::make()
                                    ->danger()
                                    ->title('Permission Denied')
                                    ->body('Only administrators can send invoice emails.')
                                    ->persistent()
                                    ->send();
                                return;
                            }
                            
                            $totalEmailsSent = 0;
                            $totalEmailsFailed = 0;
                            $completedTrips = 0;
                            
                            foreach ($records as $record) {
                                // Only process completed trips
                                if ($record->status !== 'completed') {
                                    \Illuminate\Support\Facades\Log::info("Skipping trip {$record->id} with status '{$record->status}' for bulk emails");
                                    continue;
                                }
                                
                                $completedTrips++;
                                \Illuminate\Support\Facades\Log::info("Processing completed trip {$record->id} for bulk emails");
                                
                                $hotelIds = $record->tripPassengers()->pluck('hotel_id')->unique()->filter();
                                
                                foreach ($hotelIds as $hotelId) {
                                    $hotel = Hotel::find($hotelId);
                                    if (!$hotel || !$hotel->email) continue;
                                    
                                    // Find the most recent invoice for this hotel
                                    $invoice = Invoices::where('hotel_id', $hotelId)
                                        ->orderBy('created_at', 'desc')
                                        ->first();
                                    
                                    if (!$invoice) continue;
                                    
                                    // Send the email
                                    $result = self::sendInvoiceEmail($hotel, $invoice, $record);
                                    
                                    if ($result) {
                                        $totalEmailsSent++;
                                    } else {
                                        $totalEmailsFailed++;
                                    }
                                }
                            }
                            
                            \Illuminate\Support\Facades\Log::info("Bulk email process completed: {$completedTrips} completed trips processed, {$totalEmailsSent} emails sent, {$totalEmailsFailed} emails failed");
                            
                            // Show result notification
                            if ($totalEmailsSent > 0 || $totalEmailsFailed > 0) {
                                $notificationBody = "{$totalEmailsSent} email(s) sent successfully";
                                
                                if ($totalEmailsFailed > 0) {
                                    $notificationBody .= ", {$totalEmailsFailed} email(s) failed to send";
                                }
                                
                                Notification::make()
                                    ->success()
                                    ->title('Invoice Emails Sent')
                                    ->body($notificationBody)
                                    ->persistent()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->warning()
                                    ->title('No Emails Sent')
                                    ->body('No valid hotels with email addresses were found for the selected trips.')
                                    ->persistent()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Send Bulk Invoice Emails')
                        ->modalDescription('Are you sure you want to send invoice emails for all selected completed trips?')
                        ->modalSubmitActionLabel('Yes, send emails')
                        ->deselectRecordsAfterCompletion()
                        ->visible(function () {
                            $isAdmin = self::isAdmin();
                            \Illuminate\Support\Facades\Log::info("Bulk email action visibility check: isAdmin = " . ($isAdmin ? 'true' : 'false'));
                            return $isAdmin;
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