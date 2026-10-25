<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnnualReportController extends Controller
{
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $status = (string) $request->get('status', 'لم يتم الصرف');
        $year = (string) $request->get('year', '');
        $month = (string) $request->get('month', '');
        $financialLink = (string) $request->get('financial_link', '');
        $financialCategory = (string) $request->get('financial_category', '');

        $data = $this->buildReport($status, $year, $month, $financialLink, $financialCategory);

        if ($request->boolean('export')) {
            return response()->json($this->exportRows($data));
        }

        return view('reports.annual-un-disbursed', array_merge($data, [
            'years' => $this->years(),
            'arMonths' => $this->arabicMonths(),
            'financialLinks' => \App\Models\FinancialLink::with('types')->orderBy('name')->get(),
            'status' => $status,
            'year' => $year,
            'month' => $month,
            'financialLink' => $financialLink,
            'financialCategory' => $financialCategory,
            'now' => now(),
        ]));
    }

    protected function buildReport(string $status, string $year, string $month, string $financialLink = '', string $financialCategory = ''): array
    {
        $query = DB::table('priced_delivery_permits')
            ->join('supply_requests', 'priced_delivery_permits.supply_request_number', '=', 'supply_requests.supply_request_number')
            ->leftJoin('users', 'priced_delivery_permits.created_by', '=', 'users.id')
            ->where('priced_delivery_permits.status', $status)
            ->whereNotNull('priced_delivery_permits.issue_date');

        if ($year) {
            $query->whereYear('priced_delivery_permits.issue_date', $year);
        }
        if ($month) {
            $query->whereMonth('priced_delivery_permits.issue_date', $month);
        }
        if ($financialLink !== '') {
            $query->where('supply_requests.financial_link', $financialLink);
        }
        if ($financialCategory !== '') {
            $query->where('supply_requests.financial_category', $financialCategory);
        }

        $raw = $query
            ->select(
                DB::raw("DATE_FORMAT(priced_delivery_permits.issue_date, '%Y-%m') as month_key"),
                DB::raw("DATE_FORMAT(priced_delivery_permits.issue_date, '%b-%y') as month_label"),
                'supply_requests.request_type',
                DB::raw("COALESCE(users.name, 'غير محدد') as user_name"),
                DB::raw("SUM(priced_delivery_permits.delivery_amount) as total")
            )
            ->groupBy('month_key', 'month_label', 'supply_requests.request_type', 'user_name')
            ->orderBy('month_key')
            ->get();

        $monthOrder = [];
        foreach ($raw as $row) {
            if (! isset($monthOrder[$row->month_key])) {
                $monthOrder[$row->month_key] = $row->month_label;
            }
        }
        ksort($monthOrder);

        $typeGroups = $this->buildTypeGroups($raw);

        $cells = [];
        foreach ($raw as $row) {
            $mk = $row->month_key;
            $tg = $this->typeGroupKey($row->request_type);
            $un = $row->user_name;
            $cells[$mk][$tg][$un] = (float) $row->total;
        }

        $grandTotal = 0;
        foreach ($cells as $groups) {
            foreach ($groups as $users) {
                $grandTotal += array_sum($users);
            }
        }

        $categoryTotals = [];
        foreach ($typeGroups as $tg => $data) {
            $catTotal = 0;
            foreach ($cells as $groups) {
                $catTotal += array_sum($groups[$tg] ?? []);
            }
            $categoryTotals[$tg] = $catTotal;
        }

        return compact('monthOrder', 'typeGroups', 'cells', 'grandTotal', 'categoryTotals');
    }

    /**
     * بُنيت الصفوف بنفس شكل الجدول المعروض (الشهر + أعمدة المستخدمين + إجمالي الشهر) لتُصدَّر عبر الجافاسكربت.
     */
    protected function exportRows(array $data): array
    {
        $monthOrder = $data['monthOrder'];
        $typeGroups = $data['typeGroups'];
        $cells = $data['cells'];
        $categoryTotals = $data['categoryTotals'];

        $headers = ['الشهر'];
        foreach ($typeGroups as $tgKey => $tgData) {
            foreach ($tgData['users'] as $userName) {
                $headers[] = "{$tgData['label']} - {$userName}";
            }
            $headers[] = "{$tgData['label']} - إجمالي الشهر";
        }

        $dataRows = [];
        foreach ($monthOrder as $mk => $ml) {
            $row = [$ml];
            foreach ($typeGroups as $tgKey => $tgData) {
                foreach ($tgData['users'] as $userName) {
                    $row[] = $cells[$mk][$tgKey][$userName] ?? 0;
                }
                $sub = 0;
                foreach ($tgData['users'] as $u) {
                    $sub += $cells[$mk][$tgKey][$u] ?? 0;
                }
                $row[] = $sub;
            }
            $dataRows[] = $row;
        }

        $totalRow = ['الإجمالي'];
        foreach ($typeGroups as $tgKey => $tgData) {
            foreach ($tgData['users'] as $userName) {
                $colTotal = 0;
                foreach ($monthOrder as $mk2 => $_) {
                    $colTotal += $cells[$mk2][$tgKey][$userName] ?? 0;
                }
                $totalRow[] = $colTotal;
            }
            $totalRow[] = $categoryTotals[$tgKey] ?? 0;
        }
        $dataRows[] = $totalRow;

        $dataRows[] = ['الإجمالي الكلي', $data['grandTotal']];

        return [
            'headers' => $headers,
            'rows' => $dataRows,
        ];
    }

    protected function years(): array
    {
        return DB::table('priced_delivery_permits')
            ->whereNotNull('issue_date')
            ->selectRaw('DISTINCT YEAR(issue_date) as y')
            ->orderByDesc('y')
            ->pluck('y')
            ->toArray();
    }

    protected function arabicMonths(): array
    {
        return [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];
    }

    protected function buildTypeGroups($raw): array
    {
        $groups = [];
        foreach ($raw as $row) {
            $tg = $this->typeGroupKey($row->request_type);
            if (! isset($groups[$tg])) {
                $groups[$tg] = ['label' => $tg, 'users' => []];
            }
            if (! in_array($row->user_name, $groups[$tg]['users'], true)) {
                $groups[$tg]['users'][] = $row->user_name;
            }
        }
        foreach ($groups as &$g) {
            sort($g['users']);
        }

        return $groups;
    }

    protected function typeGroupKey(?string $type): string
    {
        if (in_array($type, ['كيماويات', 'مستلزمات'], true)) {
            return 'كيماويات ومستلزمات';
        }

        return $type ?? 'غير محدد';
    }
}