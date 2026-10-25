<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $departmentId = $this->route('department')->id;
        $hospitalId = $this->input('hospital_id');

        return [
            'hospital_id' => ['required', 'exists:hospitals,id'],
            'name' => ['required', 'string', 'max:255', 'unique:departments,name,' . $departmentId . ',id,hospital_id,' . $hospitalId],
        ];
    }

    public function messages(): array
    {
        return [
            'hospital_id.required' => 'المستشفى مطلوب.',
            'hospital_id.exists' => 'المستشفى المختار غير موجود.',
            'name.required' => 'اسم القسم مطلوب.',
            'name.string' => 'اسم القسم غير صحيح.',
            'name.max' => 'اسم القسم يجب ألا يتجاوز 255 حرفاً.',
            'name.unique' => 'يوجد قسم بهذا الاسم داخل نفس المستشفى من قبل.',
        ];
    }
}