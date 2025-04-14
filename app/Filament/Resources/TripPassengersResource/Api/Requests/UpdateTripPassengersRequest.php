<?php

namespace App\Filament\Resources\TripPassengersResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTripPassengersRequest extends FormRequest
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
			'number_of_passengers' => 'required|integer',
			'excursion_charge' => 'required|numeric',
			'boat_charge' => 'required|numeric',
			'charter_charge' => 'required|numeric',
			'total_usd' => 'required|numeric',
			'total_rf' => 'required|numeric',
			'payment_method' => 'required|string',
			'payment_status' => 'required|string'
		];
    }
}
