<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
            'is_admin' => ['boolean'],
            'category' => ['nullable', 'string', Rule::in(User::CATEGORIES)],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'input_permissions' => ['nullable', 'array'],
            'input_permissions.supply_requests' => ['nullable', 'array'],
            'input_permissions.supply_requests.permitted' => ['nullable', 'boolean'],
            'input_permissions.supply_requests.fields' => ['nullable', 'array'],
            'input_permissions.supply_requests.fields.*' => ['string', Rule::in(User::supplyRequestFields())],
            'input_permissions.priced_delivery_permits' => ['nullable', 'array'],
            'input_permissions.priced_delivery_permits.permitted' => ['nullable', 'boolean'],
            'input_permissions.priced_delivery_permits.fields' => ['nullable', 'array'],
            'input_permissions.priced_delivery_permits.fields.*' => ['string', Rule::in(User::deliveryPermitFields())],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم الكامل مطلوب.',
            'username.required' => 'اسم المستخدم مطلوب.',
            'username.unique' => 'اسم المستخدم مستخدم من قبل.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 6 أحرف.',
            'category.in' => 'فئة المستخدم غير صحيحة.',
            'hospital_id.exists' => 'المستشفى المختار غير موجود.',
            'department_id.exists' => 'القسم المختار غير موجود.',
            'input_permissions.supply_requests.fields.*.in' => 'حقل طلبات الإمداد غير صحيح.',
            'input_permissions.priced_delivery_permits.fields.*.in' => 'حقل أذون التسليم غير صحيح.',
        ];
    }
}
