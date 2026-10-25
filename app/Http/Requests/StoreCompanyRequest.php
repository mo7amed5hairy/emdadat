<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:companies,name'],
            'category' => ['required', 'string', 'in:مستلزمات,أدوية,كيماويات'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الشركة مطلوب.',
            'name.unique' => 'اسم الشركة موجود من قبل.',
            'category.required' => 'فئة الشركة مطلوبة.',
            'category.in' => 'فئة الشركة غير صحيحة.',
        ];
    }
}
