<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Filament\Resources\TicketResource\RelationManagers;
use App\Models\Trip;
use App\Models\Ticket;
use App\Models\Hotel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
    ->schema([
        Forms\Components\Select::make('trip_id')
            ->label('Trip')
            ->options(
                Trip::with('tripType')
                    ->where('status', 'scheduled')
                    ->get()
                    ->mapWithKeys(fn ($trip) => [
                        $trip->id => $trip->date->format('d F y') . ' - ' . $trip->tripType->name
                    ])
            )
            ->required()
            ->searchable()
            ->reactive()
            ->afterStateUpdated(function ($state, $livewire) {
                \Log::debug('afterStateUpdated called with state: ' . $state);
                if ($state) {
                    $livewire->loadExpenses($state);
                } else {
                    // Clear expense amounts if no trip is selected
                    $currentState = $livewire->form->getState();
                    $currentState['expense_amounts'] = [];
                    $livewire->form->fill($currentState);
                }
            }),

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
            ->minValue(1),
        Forms\Components\TextInput::make('price')
            ->label('Amount ($)')
            ->numeric()
            ->required(),
        Forms\Components\Select::make('payment_status')
            ->label('Payment Status')
            ->options([
                'pending' => 'Pending',
                'paid' => 'Paid',
            ])
            ->required()
            ->default('pending'),
        Forms\Components\Select::make('payment_method')
            ->label('Payment Method')
            ->options([
                'cash' => 'Cash',
                'bank_transfer' => 'Bank Transfer',
                'credit_card' => 'Credit Card',
            ])
            ->required()
            ->default('cash'),
        Forms\Components\Toggle::make('is_hotel_ticket')
            ->label('Is Hotel Ticket')
            ->default(true),
            
        // Add Expense Details Section
        Forms\Components\Section::make('Expense Details')
            ->schema([
                Forms\Components\Repeater::make('expense_amounts')
                    ->label('Expense Amounts')
                    ->schema([
                        Forms\Components\Hidden::make('expense_id'),
                        Forms\Components\TextInput::make('expense_name')
                            ->label('Expense')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->required(),
                    ])
                    ->columnSpanFull()
                    ->disableItemCreation()
                    ->disableItemDeletion()
                    ->visible(fn ($get) => !empty($get('trip_id')))
            ])
    ]);
    }

public function loadExpenses($tripId)
{
    // Debug: Check if the method is being called and what tripId is received
    \Log::debug('loadExpenses called with tripId: ' . $tripId);
    
    // Find the trip with its related trip type and expenses
    $trip = Trip::with('tripType.expenses')->findOrFail($tripId);
    
    // Debug: Check what trip is loaded
    \Log::debug('Trip loaded: ', ['id' => $trip->id, 'tripType' => $trip->tripType ? $trip->tripType->name : 'null']);
    
    // Debug: Check if tripType exists and has expenses
    if (!$trip->tripType) {
        \Log::debug('TripType is null for trip: ' . $trip->id);
        return;
    }
    
    // Get the expenses from the trip type
    $expenses = $trip->tripType->expenses;
    
    // Debug: Check what expenses are found
    \Log::debug('Expenses found: ', ['count' => $expenses->count(), 'expenses' => $expenses->toArray()]);
    
    // Create an array of expense data for the repeater
    $expenseAmounts = $expenses->map(function ($expense) {
        return [
            'expense_id' => $expense->id,
            'expense_name' => $expense->name,
            'amount' => 0, // Default amount, can be changed by user
        ];
    })->toArray();
    
    // Debug: Check the formatted expense amounts
    \Log::debug('Formatted expense amounts: ', $expenseAmounts);
    
    // Update the form state with the expense data
    $currentState = $this->form->getState();
    $currentState['expense_amounts'] = $expenseAmounts;
    
    // Debug: Check the form state before filling
    \Log::debug('Form state before filling: ', ['has_expenses' => isset($currentState['expense_amounts']), 'count' => count($expenseAmounts)]);
    
    $this->form->fill($currentState);
    
    // Debug: Final check after filling
    \Log::debug('Form filled with expenses');
}

protected function handleRecordCreation(array $data): Model
{
    // First, extract the expense_amounts data from the form data
    $expenseAmounts = $data['expense_amounts'] ?? [];
    unset($data['expense_amounts']);
    
    // Create the ticket record
    $ticket = Ticket::create($data);
    
    // Save the expense records associated with this ticket
    foreach ($expenseAmounts as $expenseData) {
        $ticket->ticketExpenses()->create([
            'expense_id' => $expenseData['expense_id'],
            'amount' => $expenseData['amount'],
        ]);
    }
    
    return $ticket;
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('trip.date')
                    ->date('d F Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('trip.tripType.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hotel_id')
                    ->label('Hotel')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        return $record->hotel_id == null ? 'Walk in Trip' : $record->hotel->name;
                    }),
                Tables\Columns\TextColumn::make('number_of_passengers')
                    ->label('Total Passengers')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_usd')
                    ->label('Price')                    
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_hotel_ticket')
                    ->label('Hotel Ticket')
                    ->boolean(),
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
                Tables\Filters\SelectFilter::make('hotel_id')
                    ->label('Hotel')
                    ->options(Hotel::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'credit_card' => 'Credit Card',
                    ]),
                Tables\Filters\Filter::make('is_hotel_ticket')
                    ->label('Hotel Tickets Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_hotel_ticket', true)),
            ])
            ->actions([
                
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
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
