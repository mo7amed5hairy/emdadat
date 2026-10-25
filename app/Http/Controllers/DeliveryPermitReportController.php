<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\PricedDeliveryPermit;
use Illuminate\View\View;

class DeliveryPermitReportController extends Controller
{
    public function index(ReportFilterRequest $request): View|\Illuminate\Http\Response
    {
        $validated = $request->validated();

        if ($request->boolean('export')) {
            return $this->export($validated);
        }

        $query = $this->baseQuery($validated);
        $paginator = $query->clone()->paginate(8)->withQueryString();
        $totalAmount = $query->clone()->sum('delivery_amount');

        $resultCount = $paginator->total();
        $hasFilters = ! empty(array_filter($validated));

        $start = ($paginator->currentPage() - 1) * $paginator->perPage();
        $rows = $paginator->getCollection()->values()->map(function ($item, $i) use ($start) {
            return $this->renderRow($item, $start + $i + 1);
        });

        $hospitals = Hospital::with('departments')->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $companies = Company::visibleTo(auth()->user())->orderBy('name')->get();
        $years = range(2020, 2050);

        $departmentsSource = $hospitals->mapWithKeys(fn ($h) => [
            $h->id => $h->departments->sortBy('name')->values()->map(fn ($d) => ['value' => $d->id, 'label' => $d->name]),
        ]);

        return view('reports.delivery-permits', compact(
            'paginator',
            'rows',
            'totalAmount',
            'resultCount',
            'hasFilters',
            'hospitals',
            'departments',
            'companies',
            'years',
            'departmentsSource',
        ));
    }

    protected function baseQuery(array $v)
    {
        return PricedDeliveryPermit::query()
            ->with('company')
            ->when(! empty($v['month']), fn ($q) => $q->whereMonth('issue_date', $v['month']))
            ->when(! empty($v['year']), fn ($q) => $q->whereYear('issue_date', $v['year']))
            ->when(! empty($v['company_id']), fn ($q) => $q->where('company_id', $v['company_id']))
            ->when(! empty($v['date_from']), fn ($q) => $q->whereDate('issue_date', '>=', $v['date_from']))
            ->when(! empty($v['date_to']), fn ($q) => $q->whereDate('issue_date', '<=', $v['date_to']))
            ->when(! empty($v['hospital_id']), fn ($q) => $q->whereHas('supplyRequest', fn ($sq) => $sq->where('hospital_id', $v['hospital_id'])))
            ->when(! empty($v['department_id']), fn ($q) => $q->whereHas('supplyRequest', fn ($sq) => $sq->where('department_id', $v['department_id'])))
            ->latest('id');
    }

    public function export(array $v): \Illuminate\Http\Response
    {
        $rows = $this->baseQuery($v)->get();

        $headers = ['مسلسل', 'رقم إذن التسليم', 'رقم الإمداد', 'قيمة إذن التسليم', 'تاريخ إصدار الإذن', 'اسم الشركة', 'تاريخ تحرير الاستمارة/الصرف', 'حالة الإذن'];
        $dataRows = $rows->map(function ($item, $i) {
            return [
                $i + 1,
                $item->delivery_permit_number,
                $item->supply_request_number,
                (float) $item->delivery_amount,
                $item->issue_date->format('Y-m-d'),
                $item->company->name ?? '—',
                $item->form_date->format('Y-m-d'),
                $item->status,
            ];
        });

        $excel = \App\Support\Excel::makeXlsx($headers, $dataRows->toArray());

        return response($excel, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="delivery-permits-report-' . date('Ymd-His') . '.xlsx"',
        ]);
    }

    protected function renderRow(PricedDeliveryPermit $item, int $index): array
    {
        return [
            'idx' => $index,
            'noteNo' => $item->delivery_permit_number,
            'supplyNo' => $item->supply_request_number,
            'amount' => $item->delivery_amount,
            'issueDate' => $item->issue_date->format('Y-m-d'),
            'company' => $item->company->name ?? '—',
            'formDate' => $item->form_date->format('Y-m-d'),
            'status' => $item->status,
        ];
    }
}