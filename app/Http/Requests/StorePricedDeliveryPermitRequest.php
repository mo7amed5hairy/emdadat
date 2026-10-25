<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StorePricedDeliveryPermitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $permitted = $this->permittedFields();

        return [
            'delivery_amount' => in_array('delivery_amount', $permitted)
                ? ['required', 'numeric', 'min:0']
                : ['sometimes'],
            'supply_request_number' => in_array('supply_request_number', $permitted)
                ? ['required', 'string', 'max:255']
                : ['sometimes'],
            'delivery_permit_number' => in_array('delivery_permit_number', $permitted)
                ? ['required', 'string', 'max:255', 'unique:priced_delivery_permits,delivery_permit_number']
                : ['sometimes'],
            'issue_date' => in_array('issue_date', $permitted)
                ? ['required', 'date']
                : ['sometimes'],
            'company_id' => in_array('company_id', $permitted)
                ? ['required', 'exists:companies,id']
                : ['sometimes'],
            'form_date' => in_array('form_date', $permitted)
                ? ['nullable', 'date']
                : ['sometimes'],
            'status' => in_array('status', $permitted)
                ? ['required', 'in:تم الصرف,لم يتم الصرف']
                : ['sometimes'],
            'fully_delivered' => ['nullable', 'boolean'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    protected function permittedFields(): array
    {
        $all = ['delivery_amount', 'supply_request_number', 'delivery_permit_number', 'issue_date', 'company_id', 'form_date', 'status', 'fully_delivered'];
        $pf = Auth::user()->permittedInputFields('priced_delivery_permits');

        return $pf === null ? $all : $pf;
    }

    public function messages(): array
    {
        return [
            'delivery_amount.numeric' => 'القيمة يجب أن تكون رقماً.',
            'delivery_amount.min' => 'القيمة يجب أن تكون أكبر من الصفر.',
            'delivery_permit_number.unique' => 'رقم إذن التسليم مستخدم من قبل.',
            'company_id.exists' => 'الشركة المختارة غير موجودة.',
            'issue_date.date' => 'تاريخ إصدار الإذن غير صحيح.',
            'form_date.date' => 'تاريخ تحرير الاستمارة/الصرف غير صحيح.',
            'status.in' => 'حالة الإذن غير صحيحة.',
        ];
    }
}