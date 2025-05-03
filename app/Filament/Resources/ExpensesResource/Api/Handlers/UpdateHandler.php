<?php
namespace App\Filament\Resources\ExpensesResource\Api\Handlers;

use App\Models\Expenses;
use App\Models\Trip;
use App\Models\TripType;
use App\Models\ExpenseType;
use App\Models\ExpenseTypeTripType;
use Illuminate\Support\Facades\Log;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\ExpensesResource;
use App\Filament\Resources\ExpensesResource\Api\Requests\UpdateExpensesRequest;

class UpdateHandler extends Handlers {
    public static string | null $uri = '/{id}';
    public static string | null $resource = ExpensesResource::class;

    public static function getMethod()
    {
        return Handlers::PUT;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Update Expenses
     *
     * @param UpdateExpensesRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(UpdateExpensesRequest $request, $id)
    {
        // Debug log untuk melihat nilai ID yang diterima
        Log::info("Updating expense with ID: " . $id);
        Log::info("Request path: " . $request->path());
        Log::info("Request parameters: " . json_encode($request->route()->parameters()));
        
        // Coba dapatkan ID dari parameter route jika ID adalah null
        if (empty($id) && $request->route('id')) {
            $id = $request->route('id');
            Log::info("Got ID from route parameters: " . $id);
        }
        $model = static::getModel()::find($id);
        
        // Jika masih tidak ditemukan, coba cek primary key
        if (!$model) {
            Log::info("Checking if primary key is different than 'id'");
            
            // Periksa apakah model menggunakan primary key yang berbeda
            $primaryKey = (new (static::getModel()))->getKeyName();
            Log::info("Primary key name: " . $primaryKey);
            
            if ($primaryKey !== 'id') {
                $model = static::getModel()::where($primaryKey, $id)->first();
            }
        }
        
        if (!$model) {
            return response()->json([
                'message' => "Expense not found for ID: {$id}"
            ], 404);
        }
        
        $tripId = $request->input('trip_id', $model->trip_id);
        $ticketId = $request->input('ticket_id', $model->ticket_id);
        $expenseTypeId = $request->input('expense_type');
        
        // Get trip details to get trip_type_id if expense_type is changing
        if ($expenseTypeId && $expenseTypeId != $model->expense_type) {
            $trip = Trip::find($tripId);
            if (!$trip) {
                return response()->json([
                    'message' => "Trip not found for ID: {$tripId}"
                ], 404);
            }
            
            $tripTypeId = $trip->trip_type_id;
            $tripType = TripType::find($tripTypeId);
            
            $expenseType = ExpenseType::find($expenseTypeId);
            if (!$expenseType) {
                return response()->json([
                    'message' => "ExpenseType not found for ID: {$expenseTypeId}"
                ], 404);
            }
            
            // Get the default charge from the pivot table
            $expenseTypeTripType = ExpenseTypeTripType::where('trip_type_id', $tripTypeId)
                ->where('expense_type_id', $expenseTypeId)
                ->first();
                
            if (!$expenseTypeTripType) {
                return response()->json([
                    'message' => "No default charge found for TripType ID: {$tripTypeId} and ExpenseType ID: {$expenseTypeId}"
                ], 404);
            }
            
            $charge = $expenseTypeTripType->default_charge; // Get the default charge
            
            // Generate notes
            $notes = "Trip Type: {$tripType->name}, Expense Type: {$expenseType->name}, Charge: {$charge}";
            
            // Update with new charge and notes
            $model->expense_type = $expenseTypeId;
            $model->amount = $charge;
            $model->notes = $notes;
        }
        
        // Update other fields from request
        $model->fill($request->except(['expense_type', 'amount', 'notes']));
        $model->save();
        
        return response()->json([
            'success' => true,
            'data' => $model,
            'message' => "Successfully Updated Expense"
        ]);
    }
}