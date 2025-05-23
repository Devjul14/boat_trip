<?php

namespace App\Filament\Resources\TripTypeResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTripTypeRequest extends FormRequest
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
			'name' => 'required|string',
			'description' => 'required|string',
			'default_excursion_charge' => 'required|numeric',
			'default_boat_charge' => 'required|numeric',
			'default_charter_charge' => 'required|numeric'
		];
    }
}
