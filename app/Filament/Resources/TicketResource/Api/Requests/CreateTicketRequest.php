<?php

namespace App\Filament\Resources\TicketResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'trip_id' => 'required|numeric', 
            'hotel_id' => 'nullable|numeric', 
            'is_hotel_ticket' => 'nullable|boolean', 
            'number_of_passengers' => 'required|integer', 
            'total_rf' => 'required|numeric', 
            'payment_method' => 'required|string', 
            'payment_status' => 'required|string', 
        ];
    }
}
