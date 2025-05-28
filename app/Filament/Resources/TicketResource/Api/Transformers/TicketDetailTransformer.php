<?php 
namespace App\Filament\Resources\TicketResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Ticket;

/**
 * @property Ticket $resource
 */
class TicketDetailTransformer extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'trip_id' => $this->trip_id,
            'hotel' => [
                'id' => optional($this->hotel)->id,
                'name' => optional($this->hotel)->name,
                'email' => optional($this->hotel)->email,
                'phone' => optional($this->hotel)->phone,
                'address' => optional($this->hotel)->address,
                'payment_terms' => optional($this->hotel)->payment_terms,
            ], 
            'is_hotel_ticket' => $this->is_hotel_ticket,
            'number_of_passengers' => $this->number_of_passengers,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'total_amount' => $this->total_amount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'ticket_expenses' => $this->ticketExpenses->map(function ($expense) {
                return [
                    'expense_id' => $expense->expense_id,
                    'ticket_id' => $expense->ticket_id,
                    'amount' => $expense->amount,
                    'expense_name' => optional($expense->expense)->name,
                ];
            
            }),            
        ];
    }
}
