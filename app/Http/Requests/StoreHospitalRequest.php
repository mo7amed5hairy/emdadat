<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHospitalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required_without:names', 'nullable', 'string', 'max:255', 'unique:hospitals,name'],
            'names' => ['required_without:name', 'nullable', 'array', 'min:1'],
            'names.*' => ['required_with:names', 'string', 'max:255', 'unique:hospitals,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required_without' => 'اسم المستشفى مطلوب.',
            'name.string' => 'اسم المستشفى غير صحيح.',
            'name.max' => 'اسم المستشفى يجب ألا يتجاوز 255 حرفاً.',
            'name.unique' => 'اسم المستشفى مستخدم من قبل.',
            'names.required_without' => 'اسم المستشفى مطلوب.',
            'names.array' => 'البيانات غير صحيحة.',
            'names.min' => 'أدخل اسم مستشفى واحد على الأقل.',
            'names.*.required_with' => 'اسم المستشفى مطلوب.',
            'names.*.string' => 'اسم المستشفى غير صحيح.',
            'names.*.max' => 'اسم المستشفى يجب ألا يتجاوز 255 حرفاً.',
            'names.*.unique' => 'اسم المستشفى مستخدم من قبل.',
        ];
    }
}