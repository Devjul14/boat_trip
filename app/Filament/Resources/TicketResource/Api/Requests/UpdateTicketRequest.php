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
            'trip_id' => 'required',
			'hotel_id' => 'required',
			'is_hotel_ticket' => 'nullable',
			'number_of_passengers' => 'required',
			'total_rf' => 'required',
			'payment_method' => 'required',
			'payment_status' => 'required',

		];
    }
}
