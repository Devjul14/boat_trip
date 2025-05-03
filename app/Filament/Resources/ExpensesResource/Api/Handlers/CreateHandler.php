<?php
namespace App\Filament\Resources\ExpensesResource\Api\Handlers;


use App\Models\Expenses;
use App\Models\Trip;
use App\Models\TripType;
use App\Models\ExpenseType;
use App\Models\ExpenseTypeTripType;
use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\ExpensesResource;
use App\Filament\Resources\ExpensesResource\Api\Requests\CreateExpensesRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = ExpensesResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Expenses
     *
     * @param CreateExpensesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateExpensesRequest $request)
    {
        $model = new (static::getModel());
    $tripId = $request->input('trip_id');
    $ticketId = $request->input('ticket_id');
    $expenseTypeIds = $request->input('expense_type_id');
    
    // Get trip details to get trip_type_id
    $trip = Trip::find($tripId);
    if (!$trip) {
        return static::sendErrorResponse("Trip not found for ID: {$tripId}");
    }
    $tripTypeId = $trip->trip_type_id;
    $tripType = TripType::find($tripTypeId);
    
    $createdExpenses = [];
    
    foreach ($expenseTypeIds as $expenseId) {
        $expenseType = ExpenseType::find($expenseId);
        if (!$expenseType) {
            Log::error("ExpenseType not found for ID: {$expenseId}");
            continue;
        }
        
        // Get the default charge from the pivot table
        $expenseTypeTripType = ExpenseTypeTripType::where('trip_type_id', $tripTypeId)
            ->where('expense_type_id', $expenseId)
            ->first();
            
        if (!$expenseTypeTripType) {
            Log::error("No default charge found for TripType ID: {$tripTypeId} and ExpenseType ID: {$expenseId}");
            continue;
        }
        
        $charge = $expenseTypeTripType->default_charge; // Get the default charge
        
        // Generate notes
        $notes = "Trip Type: {$tripType->name}, Expense Type: {$expenseType->name}, Charge: {$charge}";
        
        // Create the expense record
        $expense = Expenses::create([
            'trip_id' => $tripId,
            'ticket_id' => $ticketId,
            'expense_type' => $expenseId,
            'amount' => $charge,
            'notes' => $notes,
        ]);
        
        $createdExpenses[] = $expense;
    }
    
    return static::sendSuccessResponse($createdExpenses, "Successfully Created Expenses");
    }
}