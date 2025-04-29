<?php

namespace App\Filament\Resources\InvoicesResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateInvoicesRequest extends FormRequest
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
			'invoice_number' => 'required|string',
			'hotel_id' => 'required',
			'trip_id' => 'required',
			'ticket_id' => 'required|integer',
			'month' => 'required|string',
			'year' => 'required|date',
			'issue_date' => 'required|date',
			'due_date' => 'required|date',
			'total_amount' => 'required|numeric',
			'status' => 'required|string'
		];
    }
}
