<?php

namespace App\Filament\Resources\TripResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTripRequest extends FormRequest
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
			'date' => 'required|date',
			'trip_type_id' => 'required',
			'boat_id' => 'required',
			'boatman_id' => 'nullable|exists:users,id',
			'remarks' => 'nullable|string',
			'status' => 'required|string',
			'petrol_consumed' => 'nullable|numeric',
			'petrol_filled' => 'nullable|numeric'
		];
    }
}
