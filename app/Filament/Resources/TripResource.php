<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripResource\Pages;
use App\Filament\Resources\TripResource\RelationManagers;
use App\Models\Trip;
use App\Models\Hotel;
use App\Models\TripType;
use App\Models\Boat;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketExpense;
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
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Facades\DB;

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
                            ->searchable(),
                        Forms\Components\Select::make('boat_id')
                            ->label('Boat')
                            ->options(Boat::pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\Hidden::make('boatman_id')
                            ->default(auth()->id()),
                        Forms\Components\Hidden::make('status')
                            ->default('scheduled'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Fuel & Remarks')
                    ->schema([
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
            ->modifyQueryUsing(function ($query) {
                if (!auth()->user()->hasRole(['Admin', 'Super Admin'])) {
                    return $query->where('boatman_id', auth()->id());
                }
                
                return $query;
            })
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
                    ->query(fn (Builder $query): Builder => $query->has('ticket')),
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
                // Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('complete_trip')
                    ->label('Complete Trip')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Trip $record) {
                        if ($record->status === 'scheduled') {
                            \Log::info("Starting trip completion process for trip ID: {$record->id}");
                            
                            // Update trip status
                            $record->update(['status' => 'completed']);
                            \Log::info("Trip status updated to 'completed'");
                            
                            // Generate invoices for each hotel in the trip
                            $ticketsByHotel = $record->ticket()->get()->groupBy('hotel_id');
                            
                            $invoiceCount = 0;
                            $ticketCount = 0;
                            
                            // Calculate issue date from trip date
                            $issueDate = $record->date;
                            $dueDate = date('Y-m-d', strtotime($issueDate . ' + 7 days'));
                            \Log::info("Issue date: {$issueDate}, Due date: {$dueDate}");
                            
                           // Get all ticket expenses for this trip (via expenses_tickets pivot)
                            $tickets = $record->ticket()->get();
                            $ticketIds = $tickets->pluck('id');
                            
                            $ticketExpenses = TicketExpense::whereIn('ticket_id', $ticketIds)->get();
                            $totalExpenseAmount = $ticketExpenses->sum('amount');
                            
                            \Log::info("Total expense amount (from pivot expenses_tickets) for trip: {$totalExpenseAmount}");

                            
                            foreach ($ticketsByHotel as $hotelId => $tickets) {
                                if (!$hotelId) {
                                    \Log::info("Skipping hotel with null ID");
                                    continue;
                                }
                                
                                $hotelName = Hotel::find($hotelId)->name ?? "Walk In Trip";
                                \Log::info("Processing hotel ID: {$hotelId} ({$hotelName})");

                                // Get total passengers for this hotel
                                $totalPassengers = $tickets->sum('number_of_passengers');
                                \Log::info("Total passengers for hotel {$hotelId}: {$totalPassengers}");
                                
                                // Calculate ticket price total
                                // $ticketPriceTotal = $tickets->sum('price');
                                // \Log::info("Total ticket price for hotel {$hotelId}: {$ticketPriceTotal}");
                                
                                // Calculate expense portion for this hotel (expense amount * passenger count)
                                $totalInvoice = $totalExpenseAmount * $totalPassengers;
                                \Log::info("Expense amount for hotel {$hotelId}: {$totalInvoice} (calculated as {$totalExpenseAmount} * {$totalPassengers})");
                                
                                // Calculate total amount = ticket price + (expense * passengers)
                                // $totalAmount = $ticketPriceTotal + $expenseForThisHotel;
                                // \Log::info("Total invoice amount for hotel {$hotelId}: {$totalAmount} (ticket price: {$ticketPriceTotal} + expense: {$expenseForThisHotel})");
                                
                                // Generate invoice number
                                $lastInvoice = Invoices::orderBy('id', 'desc')->first();
                                $lastNumber = $lastInvoice ? intval(substr($lastInvoice->invoice_number, 8, 3)) : 0;
                                $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
                                $invoiceNumber = 'AK/' . date('Y') . '/' . $newNumber;
                                \Log::info("Generated invoice number: {$invoiceNumber}");
                            
                                // Create invoice record with trip_id, issue_date, and due_date
                                $invoice = Invoices::create([
                                    'invoice_number' => $invoiceNumber,
                                    'hotel_id' => $hotelId,
                                    'trip_id' => $record->id,
                                    'month' => date('F'),
                                    'year' => date('Y'),
                                    'issue_date' => $issueDate,
                                    'due_date' => $dueDate,
                                    'total_amount' => $totalInvoice,
                                    'status' => 'draft',
                                ]);
                                \Log::info("Created invoice ID: {$invoice->id} for hotel {$hotelId}");

                                $invoiceCount++;
                                
                            }
                            
                            \Log::info("Trip completion process finished. Generated {$invoiceCount} invoices and processed {$ticketCount} tickets");
                            
                            // Show success notification
                            Notification::make()
                                ->success()
                                ->title('Trip Completed Successfully')
                                ->body("Trip has been marked as completed. {$invoiceCount} invoice(s) have been generated.")
                                ->persistent()
                                ->send();
                                
                            \Log::info("Trip completion process successful for trip {$record->id}. Generated {$invoiceCount} invoices and updated {$ticketCount} tickets");
                        } else {
                            \Log::warning("Attempted to complete trip {$record->id} but it's not in 'scheduled' status");
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Complete Trip')
                    ->modalDescription('Are you sure you want to mark this trip as completed? This will create invoice records for each hotel and generate tickets.')
                    ->modalSubmitActionLabel('Yes, complete trip')
                    ->visible(fn (Trip $record) => $record->status === 'scheduled'),

                
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
            RelationManagers\TicketRelationManager::class,
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