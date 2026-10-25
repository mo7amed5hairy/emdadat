<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hospitalId = $this->input('hospital_id');

        return [
            'hospital_id' => ['required', 'exists:hospitals,id'],
            'name' => ['required_without:names', 'nullable', 'string', 'max:255', 'unique:departments,name,NULL,id,hospital_id,' . $hospitalId],
            'names' => ['required_without:name', 'nullable', 'array', 'min:1'],
            'names.*' => ['required_with:names', 'string', 'max:255', 'unique:departments,name,NULL,id,hospital_id,' . $hospitalId],
        ];
    }

    public function messages(): array
    {
        return [
            'hospital_id.required' => 'المستشفى مطلوب.',
            'hospital_id.exists' => 'المستشفى المختار غير موجود.',
            'name.required_without' => 'اسم القسم مطلوب.',
            'name.string' => 'اسم القسم غير صحيح.',
            'name.max' => 'اسم القسم يجب ألا يتجاوز 255 حرفاً.',
            'name.unique' => 'يوجد قسم بهذا الاسم داخل نفس المستشفى من قبل.',
            'names.required_without' => 'اسم القسم مطلوب.',
            'names.array' => 'البيانات غير صحيحة.',
            'names.min' => 'أدخل اسم قسم واحد على الأقل.',
            'names.*.required_with' => 'اسم القسم مطلوب.',
            'names.*.string' => 'اسم القسم غير صحيح.',
            'names.*.max' => 'اسم القسم يجب ألا يتجاوز 255 حرفاً.',
            'names.*.unique' => 'يوجد قسم بهذا الاسم داخل نفس المستشفى من قبل.',
        ];
    }
}