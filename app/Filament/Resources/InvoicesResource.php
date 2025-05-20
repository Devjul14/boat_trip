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
use Illuminate\Support\Facades\Storage;

class InvoicesResource extends Resource
{
    protected static ?string $model = Invoices::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function canCreate(): bool
    {
        return false;
    }
    
    /**
     * Generate PDF file for hotel invoices
     *
     * @param Hotel $hotel
     * @param Collection $invoices
     * @return string Path to the generated PDF file
     */
    protected static function generateInvoicePDF(Hotel $hotel, Collection $invoices): string
{
    try {
        // Prepare data for PDF
        $data = [
            'hotel' => $hotel,
            'invoices' => $invoices,
            'totalAmount' => $invoices->sum('total_amount'),
            'generatedDate' => now()->format('d-m-Y'),
        ];

        // Process invoices to include trip information
        $invoicesData = [];
        $expensesData = []; // Initialize expenses data array
        $totalAmount = 0;
        
        foreach ($invoices as $invoice) {
            // Get associated trip
            $trip = Trip::find($invoice->trip_id);
            if (!$trip) {
                continue;
            }
            
            // Load trip type
            $trip->load('tripType');
            
            // Get passenger count for this trip and hotel
            $passengerCount = $trip->ticket()
                ->where('hotel_id', $hotel->id)
                ->sum('number_of_passengers');
            
            // Calculate the amount
            $amount = (float)$invoice->total_amount;
            $totalAmount += $amount;
            
            // Add to invoices data array
            $invoicesData[] = [
                'invoice_number' => $invoice->invoice_number,
                'trip_date' => $trip->date,
                'trip_type' => $trip->tripType->name ?? 'N/A',
                'passenger_count' => $passengerCount,
                'month_year' => "{$invoice->month}/{$invoice->year}",
                'amount' => $amount,
                'due_date' => $invoice->due_date
            ];
            
            // Get expenses for this trip that are associated with this hotel
            $tickets = Ticket::where('trip_id', $trip->id)
                ->where('hotel_id', $hotel->id)
                ->pluck('id');
                
            // Get expenses for these tickets
            $ticketExpenses = TicketExpense::with(['expense', 'ticket'])->whereIn('ticket_id', $tickets)->get();
  
            foreach ($ticketExpenses as $ticketExpense) {
                // Get ticket and expense type information
                $ticket = Ticket::find($ticketExpense->ticket_id);
                
                // Add to expenses data array - using stored values
                $expensesData[] = [
                    'trip_date' => $trip->date,
                    'trip_type' => $trip->tripType->name ?? 'N/A',
                    'expense_type' => $ticketExpense->expense->name,
                    'passenger_count' => $ticket->number_of_passengers ?? 1,
                    'amount' => $ticketExpense->amount,
                    'notes' => $trip->notes
                ];
            }
        }
        
        // Update data with processed invoice and expense information
        $data['invoicesData'] = $invoicesData;
        $data['expensesData'] = $expensesData; 
        $data['totalAmount'] = $totalAmount;
        
        // Generate PDF using the invoice PDF template
        $pdf = PDF::loadView('pdfs.invoice-summary', $data);
        
        // Set PDF options if needed
        $pdf->setPaper('a4', 'portrait');
        
        // Prepare filename - ensure it's clean and has proper extension
        $baseFileName = Str::slug("invoices_{$hotel->name}_" . date('Y-m-d_His'));
        $fileName = $baseFileName . '.pdf';
        
        // Make sure the directory exists
        $directory = storage_path('app/public/pdf');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // Full file path
        $filePath = $directory . '/' . $fileName;
        \Illuminate\Support\Facades\Log::info("PDF file path: {$filePath}");
        
        // Save the PDF file
        $pdf->save($filePath);
        
        // Verify the file was created
        if (!file_exists($filePath)) {
            throw new \Exception("Failed to create PDF file at: {$filePath}");
        }
        
        $fileSize = filesize($filePath);
        \Illuminate\Support\Facades\Log::info("PDF file generated successfully: {$filePath} (Size: {$fileSize} bytes)");
        
        return $filePath;
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error("Error generating PDF file: {$e->getMessage()}");
        \Illuminate\Support\Facades\Log::error($e->getTraceAsString());
        throw $e;
    }
}

    /**
     * Send invoice email to hotel contact person with PDF attachment
     *
     * @param Hotel $hotel
     * @param Collection $invoices
     * @return bool
     */
    protected static function sendInvoicePDFEmail(Hotel $hotel, Collection $invoices): bool
    {
        try {
            // Generate PDF file
            $pdfFilePath = self::generateInvoicePDF($hotel, $invoices);
            
            // Verify the file exists and is a PDF file
            if (!file_exists($pdfFilePath)) {
                throw new \Exception("PDF file does not exist: {$pdfFilePath}");
            }
            
            // Check file size and type
            $fileSize = filesize($pdfFilePath);
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fileInfo, $pdfFilePath);
            finfo_close($fileInfo);
            
            \Illuminate\Support\Facades\Log::info("Attaching file: {$pdfFilePath} (Size: {$fileSize} bytes, Type: {$mimeType})");
            
            // Force the correct PDF MIME type
            $pdfMimeType = 'application/pdf';
            
            // Prepare email data
            $data = [
                'hotel' => $hotel,
                'invoiceCount' => $invoices->count(),
                'totalAmount' => $invoices->sum('total_amount'),
                'contactPerson' => $hotel->contact_person,
                'month' => $invoices->first()->month,
                'year' => $invoices->first()->year
            ];
            
            \Illuminate\Support\Facades\Log::info("Sending email with PDF attachment to: {$hotel->email}");
            
            // Send email with attachment
            Mail::send('emails.invoice-pdf', $data, function($message) use ($hotel, $pdfFilePath, $data, $pdfMimeType) {
                $message->to($hotel->email, $hotel->contact_person)
                        ->subject("Invoice Summary for {$data['month']} {$data['year']}");
                
                // Always use the correct PDF MIME type and ensure the .pdf extension
                $fileName = basename($pdfFilePath);
                if (!Str::endsWith($fileName, '.pdf')) {
                    $fileName .= '.pdf';
                }
                
                // Attach the PDF file with explicit mime type
                $message->attach($pdfFilePath, [
                    'as' => $fileName,
                    'mime' => $pdfMimeType,
                ]);
            });
            
            // Update invoice statuses to 'sent'
            foreach ($invoices as $invoice) {
                $invoice->update(['status' => 'sent']);
            }
            
            // Log successful email sending
            \Illuminate\Support\Facades\Log::info("PDF invoice email sent successfully to {$hotel->email} with {$invoices->count()} invoices");
            
            return true;
        } catch (\Exception $e) {
            // Log the error but don't stop the process
            \Illuminate\Support\Facades\Log::error("Failed to send invoice PDF email: {$e->getMessage()}");
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());
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
            (method_exists($user, 'hasRole') && $user->hasRole('Admin')) || 
            $user->role === 'Admin' || 
            (property_exists($user, 'is_admin') && $user->is_admin)
        );
        
        \Illuminate\Support\Facades\Log::info("Admin check result: " . ($isAdmin ? 'true' : 'false'));
        
        return $isAdmin;
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
                        'success' => 'paid',
                        'primary' => 'sent',
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('send_invoice_pdf')
                    ->label('Send Invoice')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->action(function (Invoices $record) {
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
                        
                        $hotel = Hotel::find($record->hotel_id);
                        if (!$hotel || !$hotel->email) {
                            Notification::make()
                                ->warning()
                                ->title('Email Not Sent')
                                ->body('Hotel record not found or missing email address.')
                                ->persistent()
                                ->send();
                            return;
                        }
                        
                        // Get the trip from the invoice
                        $trip = Trip::find($record->trip_id);
                        
                        if (!$trip) {
                            Notification::make()
                                ->warning()
                                ->title('Email Not Sent')
                                ->body('No trip found for this invoice.')
                                ->persistent()
                                ->send();
                            return;
                        }

                        // Check if any passengers on this trip have already paid in cash
                        $paidCashPassengers = $trip->ticket()
                        ->where('payment_status', 'paid')
                        ->where('payment_method', 'cash')
                        ->count();

                        if ($paidCashPassengers > 0) {
                        Notification::make()
                            ->warning()
                            ->title('Payment Notification')
                            ->body('This trip has already been paid in cash by some passengers.')
                            ->persistent()
                            ->send();
                        return;
                        }
                        
                        // Create a collection with just this invoice
                        $invoices = collect([$record]);
                        
                        // Send the email with PDF attachment
                        $result = self::sendInvoicePDFEmail($hotel, $invoices);
                        
                        if ($result) {
                            Notification::make()
                                ->success()
                                ->title('Invoice Email Sent')
                                ->body("Email with PDF attachment sent successfully to {$hotel->email}")
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
                    ->modalDescription('Are you sure you want to send an invoice email with PDF attachment to the hotel?')
                    ->modalSubmitActionLabel('Yes, send email')
                    ->visible(fn (Invoices $record) => self::isAdmin() && $record->status === 'draft'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('send_bulk_invoice_emails_pdf')
                        ->label('Send PDF Invoices')
                        ->icon('heroicon-o-document')
                        ->color('success')
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
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => self::isAdmin()),
                    
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
            'edit' => Pages\EditInvoices::route('/{record}/edit'),
        ];
    }
}