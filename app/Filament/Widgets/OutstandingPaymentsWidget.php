<?php

namespace App\Filament\Widgets;

use App\Models\Invoices;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class OutstandingPaymentsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    // Set a refresh interval to keep the data up to date
    protected static ?string $pollingInterval = '60s';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Get invoices that are not paid
                Invoices::query()
                    ->where('status', '!=', 'paid')
                    ->with(['hotel'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hotel.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('month_year')
                    ->getStateUsing(fn (Invoices $record): string => 
                        $record->month . ' ' . $record->year
                    )
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->orderBy('year', $direction)
                            ->orderBy('month', $direction);
                    }),
                Tables\Columns\TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->badge()
                    ->color(fn (Invoices $record): string => 
                        $record->due_date < now() ? 'danger' : 'warning'
                    ),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => ['draft', 'sent'],
                        'danger' => 'overdue',
                    ]),
                Tables\Columns\TextColumn::make('days_overdue')
                    ->getStateUsing(function (Invoices $record): string {
                        $dueDate = Carbon::parse($record->due_date);
                        $today = Carbon::today();
                        
                        if ($dueDate < $today) {
                            return $dueDate->diffInDays($today) . ' days';
                        }
                        
                        return 'Not overdue';
                    })
                    ->badge()
                    ->color(fn (Invoices $record): string => 
                        $record->due_date < now() ? 'danger' : 'success'
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'overdue' => 'Overdue',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Only')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereDate('due_date', '<', now())
                    ),
                Tables\Filters\Filter::make('due_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('due_from'),
                        \Filament\Forms\Components\DatePicker::make('due_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['due_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '>=', $date),
                            )
                            ->when(
                                $data['due_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '<=', $date),
                            );
                    })
            ])
            ->defaultSort('due_date', 'asc')
            ->paginated([5, 10, 25, 50])
            ->heading('Outstanding Payments')
            ->description('Invoices that require payment attention');
    }
}