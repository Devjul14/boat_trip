<?php

namespace App\Filament\Resources\TicketResource\Api\Handlers;

use App\Models\Trip;
use App\Models\TicketExpense;
use Illuminate\Support\Facades\DB;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\TicketResource;
use App\Filament\Resources\TicketResource\Api\Requests\UpdateTicketRequest;

class UpdateHandler extends Handlers {
    public static string | null $uri = '/{record}';
    public static string | null $resource = TicketResource::class;

    public static function getMethod()
    {
        return Handlers::PUT;
    }

    public static function getModel()
    {
        return static::$resource::getModel();
    }

    /**
     * Update Ticket
     *
     * @param UpdateTicketRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(UpdateTicketRequest $request)
    {
        DB::beginTransaction();

        try {
            $model = static::getModel()::find($request->record);

            if (!$model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket not found.',
                    'data' => null,
                ], 404);
            }

            // ✅ Validasi trip status
            $trip = Trip::find($request->trip_id ?? $model->trip_id);
            if (!$trip) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trip not found.',
                    'data' => null,
                ], 404);
            }

            if ($trip->status !== 'scheduled') {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot update ticket. Trip status is '{$trip->status}', must be 'scheduled'.",
                    'data' => null,
                ], 400);
            }

            // 1. Update ticket
            $model->fill($request->all());
            $model->save();

            // 2. Sinkronisasi expenses
            if ($request->has('expenses') && is_array($request->expenses)) {
                // Hapus semua ticket_expenses lama
                TicketExpense::where('ticket_id', $model->id)->delete();

                // Tambahkan yang baru
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
                'message' => 'Successfully Updated Ticket with Expense',
                'data' => $responseData,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
