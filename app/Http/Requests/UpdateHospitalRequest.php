<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHospitalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:hospitals,name,' . $this->route('hospital')->id],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المستشفى مطلوب.',
            'name.string' => 'اسم المستشفى غير صحيح.',
            'name.max' => 'اسم المستشفى يجب ألا يتجاوز 255 حرفاً.',
            'name.unique' => 'اسم المستشفى مستخدم من قبل.',
        ];
    }
}