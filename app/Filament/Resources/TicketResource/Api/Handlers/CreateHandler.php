<?php

namespace App\Filament\Resources\TicketResource\Api\Handlers;

use App\Models\Expenses;
use App\Models\Trip;
use App\Models\TicketExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\TicketResource;
use App\Filament\Resources\TicketResource\Api\Requests\CreateTicketRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = TicketResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Ticket
     *
     * @param CreateTicketRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateTicketRequest $request)
    {
        DB::beginTransaction();
        
        try {
        // ✅ Validasi: Trip status must be 'scheduled'
        $trip = Trip::find($request->trip_id);
        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trip not found.',
                'data' => null
            ], 404);
        }

        if ($trip->status !== 'scheduled') {
            return response()->json([
                'success' => false,
                'message' => "Cannot create ticket. Trip status is '{$trip->status}', must be 'scheduled'.",
                'data' => null
            ], 400);
        }

        // 1. Create ticket
        $model = new (static::getModel());
        $model->fill($request->all());
        $model->save();

        // 2. Insert ke pivot expenses_tickets 
        if ($request->has('expenses') && is_array($request->expenses)) {
            foreach ($request->expenses as $expenseData) {
                if (isset($expenseData['expense_id'], $expenseData['amount'])) {
                    TicketExpense::create([
                        'expense_id' => $expenseData['expense_id'],
                        'ticket_id'  => $model->id,
                        'amount'     => $expenseData['amount'],
                    ]);
                }
            }
        }

        DB::commit();

        $ticket = $model->fresh(); 

        $expenses = TicketExpense::with('expense')
            ->where('ticket_id', $ticket->id)
            ->get()
            ->map(function ($te) {
                return [
                    'expense_id' => $te->expense_id,
                    'name'       => $te->expense->name ?? null,
                    'amount'     => $te->amount,
                ];
            });

        $responseData = $ticket->toArray();
        $responseData['expenses'] = $expenses;

        return response()->json([
            'success' => true,
            'message' => "Successfully Created Ticket with Expense",
            'data' => $responseData,
        ]);


    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'data' => null
        ], 500);
    }
    }
}
