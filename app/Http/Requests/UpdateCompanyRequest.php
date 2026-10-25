<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->route('company')->id;

        return [
            'name' => ['required', 'string', 'max:255', 'unique:companies,name,' . $companyId],
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
