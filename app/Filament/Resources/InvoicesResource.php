<?php

namespace App\Filament\Resources;


use App\Filament\Resources\InvoicesResource\Pages;
use App\Filament\Resources\InvoicesResource\RelationManagers;
use App\Models\TicketExpense;
use App\Models\Expenses;
use App\Models\Invoices;
use App\Models\Ticket;
use App\Models\Hotel;
use App\Models\Trip;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use PDF;
use Illuminate\Support\Str;
use App\Services\InvoiceMailService;
use Illuminate\Support\Facades\Storage;

class InvoicesResource extends Resource
{
    protected static ?string $model = Invoices::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function canCreate(): bool
    {
        return false;
    }   

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Details')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g. AK/2025/001'),
                        Forms\Components\Select::make('hotel_id')
                            ->label('Hotel')
                            ->relationship('hotel', 'name')
                            ->required()
                            ->searchable(),
                        // Forms\Components\Select::make('trip_id')
                        //     ->label('Trip')
                        //     ->relationship('trip', 'id')
                        //     ->getOptionLabelFromRecordUsing(fn (Trip $record) => "Trip #{$record->tripType->name} - " . date('d/m/Y', strtotime($record->date)))
                        //     ->searchable(),
                        Forms\Components\DatePicker::make('issue_date')
                            ->label('Issue Date')
                            ->required()
                            ->default(now()),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')
                            ->required()
                            ->default(now()->addDays(7)),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Payment Information')
                    ->schema([
                        Forms\Components\TextInput::make('month')
                            ->required()
                            ->default(date('F')),
                        Forms\Components\TextInput::make('year')
                            ->required()
                            ->default(date('Y')),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount ($)')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent',
                                'paid' => 'Paid',
                                'overdue' => 'Overdue',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('draft'),
                    ])
                    ->columns(2),
                // Forms\Components\Section::make('Additional Information')
                //     ->schema([
                //         Forms\Components\Textarea::make('notes')
                //             ->rows(3)
                //             ->columnSpan('full'),
                //     ])
                //     ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('hotel.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('month')
                    ->searchable(),
                Tables\Columns\TextColumn::make('year'),
                Tables\Columns\TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'cancelled',
                        'warning' => ['draft', 'overdue'],
                        'primary' => 'paid',
                        'success' => 'sent',
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('hotel_id')
                    ->label('Hotel')
                    ->relationship('hotel', 'name'),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('issue_date_from'),
                        Forms\Components\DatePicker::make('issue_date_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['issue_date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('issue_date', '>=', $date),
                            )
                            ->when(
                                $data['issue_date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('issue_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('send_invoice_pdf')
                    ->label('Send Invoice')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->visible(function (Invoices $record) {
                        return $record->status === 'draft' && optional($record->trip)->status === 'completed';
                    })
                    ->action(function (Invoices $record) {
                        \Log::info("Send invoice action triggered for invoice ID: {$record->id}");

                        $hotel = Hotel::find($record->hotel_id);
                        if (!$hotel || !$hotel->email) {
                            \Log::warning("Hotel not found or missing email for invoice ID: {$record->id}");
                            Notification::make()
                                ->warning()
                                ->title('Email Not Sent')
                                ->body('Hotel record not found or missing email address.')
                                ->persistent()
                                ->send();
                            return;
                        }

                        $trip = Trip::find($record->trip_id);
                        if (!$trip) {
                            \Log::warning("Trip not found for invoice ID: {$record->id}");
                            Notification::make()
                                ->warning()
                                ->title('Email Not Sent')
                                ->body('No trip found for this invoice.')
                                ->persistent()
                                ->send();
                            return;
                        }

                        $paidCashPassengers = $trip->ticket()
                            ->where('payment_status', 'paid')
                            ->where('payment_method', 'cash')
                            ->count();

                        \Log::info("Found {$paidCashPassengers} cash-paid passengers for trip ID: {$trip->id}");

                        if ($paidCashPassengers > 0) {
                            \Log::warning("Trip has cash-paid passengers; aborting email.");
                            Notification::make()
                                ->warning()
                                ->title('Payment Notification')
                                ->body('This trip has already been paid in cash by some passengers.')
                                ->persistent()
                                ->send();
                            return;
                        }

                        \Log::info("Preparing to send invoice email to {$hotel->email} for invoice ID: {$record->id}");

                        // Panggil service
                        $mailService = app(InvoiceMailService::class);
                        $result = $mailService->send($hotel, collect([$record]));

                        if ($result) {
                            Notification::make()
                                ->success()
                                ->title('Invoice Email Sent')
                                ->body("Email sent successfully to {$hotel->email}")
                                ->persistent()
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Email Failed')
                                ->body("Failed to send email to {$hotel->email}")
                                ->persistent()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Send Invoice Email')
                    ->modalDescription('Are you sure you want to send an invoice email with a view link to the hotel?')
                    ->modalSubmitActionLabel('Yes, send email')

                ])
           
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('send_bulk_invoice_emails_pdf')
                        ->label('Send PDF Invoices')
                        ->icon('heroicon-o-document')
                        ->color('success')
                        ->action(function (Collection $records) {
                            
                            // Check if the PDF library is installed
                            if (!class_exists('Barryvdh\DomPDF\Facade\Pdf') && !class_exists('PDF')) {
                                Notification::make()
                                    ->danger()
                                    ->title('Missing Dependency')
                                    ->body('PDF library is not installed. Please run: composer require barryvdh/laravel-dompdf')
                                    ->persistent()
                                    ->send();
                                return;
                            }
                            
                            // Filter only draft invoices
                            $draftInvoices = $records->filter(function ($record) {
                                return $record->status === 'draft';
                            });
                            
                            if ($draftInvoices->isEmpty()) {
                                Notification::make()
                                    ->warning()
                                    ->title('No Draft Invoices')
                                    ->body('Only draft invoices can be sent. Please select at least one draft invoice.')
                                    ->persistent()
                                    ->send();
                                return;
                            }
                            
                            // Group invoices by hotel
                            $invoicesByHotel = $draftInvoices->groupBy('hotel_id');
                            
                            $emailsSent = 0;
                            $emailsFailed = 0;
                            $hotels = 0;
                            
                            foreach ($invoicesByHotel as $hotelId => $hotelInvoices) {
                                $hotel = Hotel::find($hotelId);
                                if (!$hotel || !$hotel->email) continue;
                                
                                $hotels++;
                                
                                // Send the PDF with all the hotel's invoices
                                $result = self::sendInvoicePDFEmail($hotel, $hotelInvoices);
                                
                                if ($result) {
                                    $emailsSent++;
                                } else {
                                    $emailsFailed++;
                                }
                            }
                            
                            // Show result notification
                            if ($emailsSent > 0 || $emailsFailed > 0) {
                                $notificationBody = "PDF invoice emails sent to {$emailsSent} hotels";
                                
                                if ($emailsFailed > 0) {
                                    $notificationBody .= ", failed for {$emailsFailed} hotels";
                                }
                                
                                Notification::make()
                                    ->success()
                                    ->title('PDF Invoice Emails Sent')
                                    ->body($notificationBody)
                                    ->persistent()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->warning()
                                    ->title('No Emails Sent')
                                    ->body('No valid hotels with email addresses were found for the selected invoices.')
                                    ->persistent()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Send PDF Invoice Emails')
                        ->modalDescription('This will group invoices by hotel and send PDF attachments with all invoices for each hotel.')
                        ->modalSubmitActionLabel('Yes, send PDF invoices')
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('mark_as_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $updated = 0;
                            
                            foreach ($records as $record) {
                                if ($record->status !== 'paid') {
                                    $record->update(['status' => 'paid']);
                                    $updated++;
                                }
                            }
                            
                            Notification::make()
                                ->success()
                                ->title('Invoices Updated')
                                ->body("{$updated} invoice(s) marked as paid.")
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Mark Invoices as Paid')
                        ->modalDescription('Are you sure you want to mark these invoices as paid?')
                        ->modalSubmitActionLabel('Yes, mark as paid')
                        ->deselectRecordsAfterCompletion(),
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoices::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoices::route('/{record}/edit'),
        ];
    }
}