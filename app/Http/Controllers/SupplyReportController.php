<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\SupplyRequest;
use Illuminate\View\View;

class SupplyReportController extends Controller
{
    public function index(ReportFilterRequest $request): View|\Illuminate\Http\Response
    {
        $validated = $request->validated();

        if ($request->boolean('export')) {
            return $this->export($validated);
        }

        $query = $this->baseQuery($validated);
        $paginator = $query->clone()->paginate(8)->withQueryString();
        $totalAmount = $query->clone()->sum('amount');

        $resultCount = $paginator->total();
        $hasFilters = ! empty(array_filter($validated));

        $start = ($paginator->currentPage() - 1) * $paginator->perPage();
        $rows = $paginator->getCollection()->values()->map(function ($item, $i) use ($start) {
            return $this->renderRow($item, $start + $i + 1);
        });

        $hospitals = Hospital::with('departments')->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $years = range(2020, 2050);

        $fiscalYears = collect();
        for ($start = 2020; $start <= 2040; $start++) {
            $fiscalYears->push($start . '-' . ($start + 1));
        }

        $departmentsSource = $hospitals->mapWithKeys(fn ($h) => [
            $h->id => $h->departments->sortBy('name')->values()->map(fn ($d) => ['value' => $d->id, 'label' => $d->name]),
        ]);

        return view('reports.supply-requests', compact(
            'paginator',
            'rows',
            'totalAmount',
            'resultCount',
            'hasFilters',
            'hospitals',
            'departments',
            'years',
            'fiscalYears',
            'departmentsSource',
        ));
    }

    protected function baseQuery(array $v)
    {
        return SupplyRequest::query()
            ->with(['hospital', 'department'])
            ->when(! empty($v['month']), fn ($q) => $q->whereMonth('request_date', $v['month']))
            ->when(! empty($v['year']), fn ($q) => $q->whereYear('request_date', $v['year']))
            ->when(! empty($v['type']), fn ($q) => $q->where('request_type', $v['type']))
            ->when(! empty($v['date_from']), fn ($q) => $q->whereDate('request_date', '>=', $v['date_from']))
            ->when(! empty($v['date_to']), fn ($q) => $q->whereDate('request_date', '<=', $v['date_to']))
            ->when(! empty($v['hospital_id']), fn ($q) => $q->where('hospital_id', $v['hospital_id']))
            ->when(! empty($v['department_id']), fn ($q) => $q->where('department_id', $v['department_id']))
            ->when(! empty($v['fiscal_year']), fn ($q) => $q->where('fiscal_year', $v['fiscal_year']))
            ->latest('id');
    }

    public function export(array $v): \Illuminate\Http\Response
    {
        $rows = $this->baseQuery($v)->get();

        $headers = ['مسلسل', 'نوع الطلب', 'رقم طلب الإمداد', 'المستشفى', 'القسم', 'الارتباط المالي', 'نوع الارتباط المالي', 'قيمة طلب الإمداد', 'تاريخ ورود الطلب'];
        $dataRows = $rows->map(function ($item, $i) {
            return [
                $i + 1,
                $item->request_type,
                $item->supply_request_number,
                $item->hospital->name ?? '—',
                $item->department->name ?? '—',
                $item->financial_link,
                $item->financial_category,
                (float) $item->amount,
                $item->request_date->format('Y-m-d'),
            ];
        });

        $excel = \App\Support\Excel::makeXlsx($headers, $dataRows->toArray());

        return response($excel, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="supply-requests-report-' . date('Ymd-His') . '.xlsx"',
        ]);
    }

    protected function renderRow(SupplyRequest $item, int $index): array
    {
        return [
            'idx' => $index,
            'type' => $item->request_type,
            'requestNo' => $item->supply_request_number,
            'hospital' => $item->hospital->name ?? '—',
            'department' => $item->department->name ?? '—',
            'financial' => $item->financial_link . ' — ' . $item->financial_category,
            'amount' => $item->amount,
            'date' => $item->request_date->format('Y-m-d'),
        ];
    }
}