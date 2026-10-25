<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFinancialLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:financial_links,name,' . $this->route('financialLink')->id],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الارتباط المالي مطلوب.',
            'name.unique' => 'الارتباط المالي مستخدم من قبل.',
        ];
    }
}