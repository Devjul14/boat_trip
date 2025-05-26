<?php

namespace App\Filament\Resources\TicketResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
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
        'trip_id' => 'required|exists:trips,id',
        'hotel_id' => 'nullable|exists:hotels,id',
        'is_hotel_ticket' => 'nullable|boolean',
        'number_of_passengers' => 'required|integer|min:1',
        'payment_method' => 'required|string',
        'payment_status' => 'required|string',

        'expenses' => 'nullable|array',
        'expenses.*.expense_id' => 'required|exists:expense,id',
        'expenses.*.amount' => 'required|numeric|min:0',
    ];
}

}
