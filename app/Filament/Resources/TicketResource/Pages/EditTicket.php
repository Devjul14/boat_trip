<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Filament\Resources\Pages\EditRecord;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Card;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;
    
    protected $expenses = [];
    
    // Run when the form is first loaded
    public function mount($record = null): void
    {
        parent::mount($record);
        
        if ($this->record && $this->record->trip_id) {
            $this->loadExpenses($this->record->trip_id);
        }
    }
    
    // Reset expense fields
    public function resetExpenseAmounts(): void
    {
        $this->expenses = [];
        $this->updateExpenseFields();
    }
    
    // Load expenses from the selected trip
    public function loadExpenses($tripId): void
    {
        $trip = Trip::with('tripType.expenses')->find($tripId);
        
        if ($trip && $trip->tripType) {
            $this->expenses = [];
            
            foreach ($trip->tripType->expenses as $expense) {
                $amount = 0;
                
                // If editing an existing record, fetch the current amount
                if ($this->record) {
                    $expenseTicket = DB::table('expenses_tickets')
                        ->where('ticket_id', $this->record->id)
                        ->where('expense_id', $expense->id)
                        ->first();
                    
                    if ($expenseTicket) {
                        $amount = $expenseTicket->amount;
                    }
                }
                
                $this->expenses[] = [
                    'id' => $expense->id,
                    'name' => $expense->name,
                    'amount' => $amount
                ];
            }
           
        }
    }
    
   
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Extract expense data before updating the ticket
        $expenseAmounts = $data['expense_amounts'] ?? [];
        
        // Remove expense_amounts from the data array since it's not a database field
        unset($data['expense_amounts']);
        
        // Update the ticket
        $record->update($data);
        
        // Delete existing expense_tickets for this ticket
        DB::table('expenses_tickets')
            ->where('ticket_id', $record->id)
            ->delete();
        
        // Now save the expenses to the pivot table
        foreach ($expenseAmounts as $expense) {
            if (isset($expense['expense_id']) && isset($expense['amount'])) {
                DB::table('expenses_tickets')->insert([
                    'expense_id' => $expense['expense_id'],
                    'ticket_id' => $record->id,
                    'amount' => $expense['amount']
                ]);
            }
        }
        
        return $record;
    }
}