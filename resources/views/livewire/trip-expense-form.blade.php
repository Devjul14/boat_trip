<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Trip;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;

class TripExpenseForm extends Component implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    public ?int $trip_id = null;

    public array $expense_amounts = [];

    public function mount(): void
    {
        $this->form->fill([
            'trip_id' => $this->trip_id,
            'expense_amounts' => [],
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('trip_id')
                ->label('Trip')
                ->options(
                    Trip::with('tripType')
                        ->where('status', 'scheduled')
                        ->get()
                        ->mapWithKeys(fn ($trip) => [
                            $trip->id => $trip->date->format('d F y') . ' - ' . ($trip->tripType?->name ?? 'N/A'),
                        ])->toArray()
                )
                ->required()
                ->searchable()
                ->reactive()
                ->afterStateUpdated(fn ($state) => $this->trip_id = $state), // set public property
            Section::make('Expense Details')
                ->schema([
                    Repeater::make('expense_amounts')
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
                        ->visible(fn ($get) => !empty($get('trip_id'))),
                ]),
        ];
    }

    public function updatedTripId($tripId): void
    {
        if ($tripId) {
            $this->loadExpenses($tripId);
        } else {
            $this->expense_amounts = [];
            $this->form->fill([
                'expense_amounts' => [],
            ]);
        }
    }

    public function loadExpenses(int $tripId): void
    {
        \Log::debug('loadExpenses called with tripId: ' . $tripId);

        $trip = Trip::with('tripType.expenses')->find($tripId);

        if (!$trip || !$trip->tripType) {
            \Log::debug('Trip or TripType not found for tripId: ' . $tripId);
            $this->expense_amounts = [];
            return;
        }

        $expenses = $trip->tripType->expenses;

        \Log::debug('Expenses found: ' . $expenses->count());

        $this->expense_amounts = $expenses->map(fn ($expense) => [
            'expense_id' => $expense->id,
            'expense_name' => $expense->name,
            'amount' => 0,
        ])->toArray();

        $this->form->fill([
            'expense_amounts' => $this->expense_amounts,
        ]);
        \Log::debug('Form filled with expense amounts');
    }

    public function render()
    {
        return view('livewire.trip-expense-form')
            ->layout('layouts.app'); // sesuaikan dengan layout Anda
    }
}

