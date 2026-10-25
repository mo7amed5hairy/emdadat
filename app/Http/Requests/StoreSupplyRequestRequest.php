<?php

namespace App\Http\Requests;

use App\Models\FinancialLink;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreSupplyRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * القواعد الديناميكية بناءً على صلاحيات المستخدم.
     */
    public function rules(): array
    {
        $permitted = $this->permittedFields();

        return [
            'request_type' => ['nullable', 'string', Rule::in(['أدوية', 'مستلزمات', 'كيماويات'])],
            'supply_request_number' => in_array('supply_request_number', $permitted)
                ? ['required', 'string', 'max:255', 'unique:supply_requests,supply_request_number']
                : ['sometimes'],
            'serial' => in_array('serial', $permitted)
                ? ['nullable', 'string', 'max:255']
                : ['sometimes'],
            'hospital_id' => in_array('hospital_id', $permitted)
                ? ['required', 'exists:hospitals,id']
                : ['sometimes'],
            'department_id' => in_array('department_id', $permitted)
                ? ['required', 'exists:departments,id']
                : ['sometimes'],
            'financial_link' => in_array('financial_link', $permitted)
                ? ['required', 'exists:financial_links,name', Rule::in($this->availableFinancialLinks())]
                : ['sometimes'],
            'financial_category' => in_array('financial_category', $permitted)
                ? ['required', Rule::in($this->availableFinancialCategories())]
                : ['sometimes'],
            'amount' => in_array('amount', $permitted)
                ? ['required', 'numeric', 'min:0']
                : ['sometimes'],
            'request_date' => in_array('request_date', $permitted)
                ? ['required', 'date']
                : ['sometimes'],
            'fiscal_year' => in_array('fiscal_year', $permitted)
                ? ['nullable', 'string', 'max:9', 'regex:/^\d{4}-\d{4}$/']
                : ['sometimes'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * الحقول المسموحة للمستخدم الحالي (null = كل الحقول مسموحة).
     */
    protected function permittedFields(): array
    {
        $permitted = Auth::user()->permittedInputFields('supply_requests');

        return $permitted ?? [
            'supply_request_number',
            'serial',
            'hospital_id',
            'department_id',
            'financial_link',
            'financial_category',
            'amount',
            'request_date',
            'fiscal_year',
        ];
    }

    protected function availableFinancialLinks(): array
    {
        return FinancialLink::pluck('name')->all();
    }

    protected function availableFinancialCategories(): array
    {
        $link = FinancialLink::where('name', $this->input('financial_link'))->first();

        return $link ? $link->types->pluck('name')->all() : [];
    }

    public function messages(): array
    {
        return [
            'supply_request_number.unique' => 'رقم طلب الإمداد مستخدم من قبل.',
            'hospital_id.exists' => 'المستشفى المختار غير موجود.',
            'department_id.exists' => 'القسم المختار غير موجود.',
            'financial_link.exists' => 'الارتباط المالي غير موجود.',
            'financial_link.in' => 'الارتباط المالي غير صحيح.',
            'financial_category.in' => 'نوع الارتباط المالي غير صحيح.',
            'amount.numeric' => 'القيمة يجب أن تكون رقماً.',
            'amount.min' => 'القيمة يجب أن تكون أكبر من الصفر.',
            'request_date.date' => 'تاريخ ورود الطلب غير صحيح.',
            'fiscal_year.regex' => 'العام المالي غير صحيح.',
        ];
    }
}