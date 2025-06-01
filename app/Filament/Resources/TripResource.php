<?php

namespace App\Filament\Resources;

use App\Services\TripCompletionService;
use App\Filament\Resources\TripResource\Pages;
use App\Filament\Resources\TripResource\RelationManagers;
use App\Models\Trip;
use App\Models\Hotel;
use App\Models\Invoices;
use App\Models\Ticket;
use App\Models\TicketExpense;
use App\Models\TripType;
use App\Models\Boat;
use App\Models\User;
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
                        try {
                            $service = new TripCompletionService();
                            $result = $service->complete($record);

                            Notification::make()
                                ->success()
                                ->title('Trip Completed Successfully')
                                ->body("Trip marked as completed. {$result['invoice_count']} invoice(s) created.")
                                ->persistent()
                                ->send();

                            \Log::info("Trip ID {$result['trip_id']} completed on {$result['date']}. Total invoices: {$result['invoice_count']}");

                        } catch (\Exception $e) {
                            \Log::error("Trip completion FAILED for trip ID {$record->id}: " . $e->getMessage());

                            Notification::make()
                                ->danger()
                                ->title('Trip Completion Failed')
                                ->body("Error: " . $e->getMessage())
                                ->persistent()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Complete Trip')
                    ->modalDescription('Are you sure you want to mark this trip as completed? This will create invoice records and set payment statuses.')
                    ->modalSubmitActionLabel('Yes, complete trip')
                    ->visible(fn (Trip $record) => $record->status === 'scheduled')
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