<?php
namespace App\Filament\Resources\TicketResource\Api\Handlers;

use App\Models\Expenses;
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
        // Begin transaction to ensure data integrity
        DB::beginTransaction();
        
        try {
            // Create ticket
            $model = new (static::getModel());
            $model->fill($request->all());
            $model->save();
            
            // $expenseIds = []; // Array to collect expense IDs
            
            // if ($request->has('expenses') && is_array($request->expenses)) {
            //     // First, detach any existing expenses to avoid duplicates
            //     $model->expenses()->detach();
                
            //     foreach ($request->expenses as $expenseData) {
            //         $expense = new Expenses();
            //         $expense->trip_id = $model->trip_id;
            //         $expense->ticket_id = $model->id;
            //         $expense->expense_type = $expenseData['expense_type'];
            //         $expense->amount = $expenseData['amount'];
            //         $expense->notes = $expenseData['notes'] ?? null;
            //         $expense->save();
                    
            //         // Add to our array of IDs
            //         $expenseIds[] = $expense->id;
            //     }
                
            //     // Attach all expenses at once
            //     if (!empty($expenseIds)) {
            //         $model->expenses()->attach($expenseIds);
            //     }
            // }
            
            DB::commit();
            return static::sendSuccessResponse($model, "Successfully Created Ticket with Expense");
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