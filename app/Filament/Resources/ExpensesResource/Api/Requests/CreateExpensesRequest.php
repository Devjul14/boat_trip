<?php

namespace App\Filament\Resources\ExpensesResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateExpensesRequest extends FormRequest
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
            'ticket_id' => 'nullable|exists:tickets,id',
            'expense_type_id' => 'required|array',
            'expense_type_id.*' => 'exists:expense_types,id',
        ];
    }
}
