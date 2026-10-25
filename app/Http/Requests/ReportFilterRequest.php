<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
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
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'type' => ['nullable', 'in:أدوية,مستلزمات,كيماويات'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'hospital_id' => ['nullable', 'exists:hospitals,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'fiscal_year' => ['nullable', 'string', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}