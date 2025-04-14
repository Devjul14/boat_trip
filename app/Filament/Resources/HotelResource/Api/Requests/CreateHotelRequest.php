<?php

namespace App\Filament\Resources\HotelResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateHotelRequest extends FormRequest
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
			'contact_person' => 'required|string',
			'email' => 'required|string',
			'phone' => 'required|string',
			'address' => 'required|string',
			'payment_terms' => 'required|string'
		];
    }
}
