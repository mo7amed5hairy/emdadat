<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplyRequestRequest;
use App\Http\Requests\UpdateSupplyRequestRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\FinancialLink;
use App\Models\Hospital;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SupplyRequestController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = SupplyRequest::query()
            ->with(['hospital', 'department', 'createdBy'])
            ->withCount('deliveryPermits')
            ->withSum('deliveryPermits', 'delivery_amount')
            ->latest('id')
            ->visibleTo(auth()->user());

        if ($term = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($term) {
                $q->where('request_type', 'like', "%{$term}%")
                    ->orWhere('supply_request_number', 'like', "%{$term}%")
                    ->orWhere('serial', 'like', "%{$term}%")
                    ->orWhere('financial_link', 'like', "%{$term}%")
                    ->orWhere('financial_category', 'like', "%{$term}%")
                    ->orWhereHas('hospital', fn ($h) => $h->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('department', fn ($d) => $d->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('createdBy', fn ($u) => $u->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->boolean('export')) {
            return response()->json([
                'rows' => $query->get()->map(fn ($item) => $this->renderRow($item))->values(),
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            $page = max(1, (int) $request->get('page', 1));
            $perPage = 8;

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json($this->tablePayload($paginator));
        }

        $hospitals = Hospital::orderBy('name')->get();
        $companies = Company::visibleTo(auth()->user())->orderBy('name')->get();
        $financialLinks = FinancialLink::with('types')->orderBy('name')->get();
        $fiscalYears = collect();
        for ($start = 2020; $start <= 2040; $start++) {
            $fiscalYears->push($start . '-' . ($start + 1));
        }

        $userHospitalId = auth()->user()->hospital_id;
        $userDepartmentId = auth()->user()->department_id;
        $permittedFields = auth()->user()->permittedInputFields('supply_requests');
        /* مسؤول النظام لا يُسند إليه طلب (مستخدمه في دروب داون صاحب الطلب مستبعد) */
        $users = User::where('is_admin', false)->orderBy('name')->get();

        return view('supply-requests.index', compact('hospitals', 'companies', 'financialLinks', 'fiscalYears', 'userHospitalId', 'userDepartmentId', 'permittedFields', 'users'));
    }

    public function lookup(Request $request): JsonResponse
    {
        $number = trim((string) $request->query('number', ''));

        if ($number === '') {
            return response()->json(['found' => false, 'message' => 'أدخل رقم الإمداد أولًا.']);
        }

        $item = SupplyRequest::query()
            ->with(['hospital', 'department'])
            ->withCount('deliveryPermits')
            ->withSum('deliveryPermits', 'delivery_amount')
            ->visibleTo(auth()->user())
            ->where('supply_request_number', $number)
            ->first();

        if (! $item) {
            return response()->json([
                'found' => false,
                'message' => 'طلب الإمداد الذي بالرقم ده غير موجود.',
            ]);
        }

        return response()->json([
            'found' => true,
            'data' => [
                'request_type' => $item->request_type,
                'supply_request_number' => $item->supply_request_number,
                'hospital' => $item->hospital?->name ?? '—',
                'department' => $item->department?->name ?? '—',
                'financial_link' => $item->financial_link ?: '—',
                'financial_category' => $item->financial_category ?: '—',
                'amount' => $item->amount,
                'request_date' => optional($item->request_date)->format('Y-m-d') ?? '—',
                'permits_count' => $item->delivery_permits_count ?? 0,
                'delivered_amount' => (float) ($item->delivery_permits_sum_delivery_amount ?? 0),
                'remaining' => (float) $item->amount - (float) ($item->delivery_permits_sum_delivery_amount ?? 0),
            ],
        ]);
    }

    public function store(StoreSupplyRequestRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = auth()->user();

        $assignedTo = (int) ($data['assigned_to'] ?? $user->id);
        $data['created_by'] = $user->id;
        $data['assigned_to'] = $assignedTo;
        $owner = User::find($assignedTo);
        $data['request_type'] = $owner?->category ?? $user->category ?? null;
        $permitted = $user->permittedInputFields('supply_requests');

        if ($permitted !== null) {
            if (! in_array('supply_request_number', $permitted, true) || empty($data['supply_request_number'])) {
                $data['supply_request_number'] = $this->generateSupplyNumber();
            }
            if (! in_array('hospital_id', $permitted, true)) {
                $data['hospital_id'] = $owner?->hospital_id ?? $user->hospital_id;
            }
            if (! in_array('department_id', $permitted, true)) {
                $data['department_id'] = $owner?->department_id ?? $user->department_id;
            }
        }

        $supplyRequest = SupplyRequest::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة طلب الإمداد بنجاح.',
            'request' => $this->renderRow($supplyRequest),
        ], 201);
    }

    public function update(UpdateSupplyRequestRequest $request, SupplyRequest $supplyRequest): JsonResponse
    {
        $data = $request->validated();
        $user = auth()->user();
        $permitted = $user->permittedInputFields('supply_requests');

        if (! empty($data['assigned_to'])) {
            $data['assigned_to'] = (int) $data['assigned_to'];
        } else {
            unset($data['assigned_to']);
        }
        $data['created_by'] = $supplyRequest->created_by ?? $user->id;

        $owner = User::find($data['assigned_to'] ?? $supplyRequest->assigned_to);
        $data['request_type'] = $owner?->category ?? $user->category ?? null;

        $finalOwner = (int) ($data['assigned_to'] ?? $supplyRequest->assigned_to);
        if ($finalOwner !== (int) $supplyRequest->assigned_to) {
            // إسناد لمستخدم جديد — المالك الجديد لم يتعامل معه بعد فيبقى ملوّنًا.
            $data['owner_processed_at'] = null;
        } elseif ((int) $user->id === $finalOwner && (int) $supplyRequest->created_by !== (int) $user->id) {
            // المالك المُسند إليه دخل وكمل/عدّل وحفظ — يرجع الصف لونه العادي.
            $data['owner_processed_at'] = now();
        }

        if ($permitted !== null) {
            if (! in_array('supply_request_number', $permitted, true)) {
                unset($data['supply_request_number']);
            }
            if (empty($data['supply_request_number']) && ! in_array('supply_request_number', $permitted, true)) {
                $data['supply_request_number'] = $supplyRequest->supply_request_number;
            }
            if (! in_array('hospital_id', $permitted, true)) {
                $data['hospital_id'] = $supplyRequest->hospital_id;
            }
            if (! in_array('department_id', $permitted, true)) {
                $data['department_id'] = $supplyRequest->department_id;
            }
        }

        $supplyRequest->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل طلب الإمداد بنجاح.',
            'request' => $this->renderRow($supplyRequest->fresh()),
        ]);
    }

    public function destroy(SupplyRequest $supplyRequest): JsonResponse
    {
        $supplyRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف طلب الإمداد بنجاح.',
        ]);
    }

    /**
     * Build the JSON payload consumed by the shared DataTable renderer.
     */
    protected function tablePayload(LengthAwarePaginator $paginator): array
    {
        $rows = $paginator->getCollection()->map(fn ($item) => $this->renderRow($item));

        $pages = $this->pageList($paginator->currentPage(), $paginator->lastPage());

        return [
            'rows' => $rows,
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'perPage' => $paginator->perPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'pages' => $pages,
            'grandTotal' => SupplyRequest::visibleTo(auth()->user())->sum('amount'),
            'budgetCount' => SupplyRequest::visibleTo(auth()->user())->where('financial_link', 'ميزانية')->count(),
            'budgetAmount' => SupplyRequest::visibleTo(auth()->user())->where('financial_link', 'ميزانية')->sum('amount'),
            'specialCount' => SupplyRequest::visibleTo(auth()->user())->where('financial_link', 'حسابات خاصة')->count(),
            'specialAmount' => SupplyRequest::visibleTo(auth()->user())->where('financial_link', 'حسابات خاصة')->sum('amount'),
        ];
    }

    /**
     * Render a single row as HTML for the DataTable body.
     */
    protected function renderRow(SupplyRequest $item): array
    {
        return [
            'id' => $item->id,
            'type' => $item->request_type,
            'requestNo' => $item->supply_request_number ?? '—',
            'serial' => $item->serial ?? '—',
            'hospitalId' => $item->hospital_id,
            'departmentId' => $item->department_id,
            'hospital' => $item->hospital?->name ?? '—',
            'department' => $item->department?->name ?? '—',
            'financialLink' => $item->financial_link ?? '—',
            'financialCategory' => $item->financial_category ?? '—',
            'amount' => $item->amount ?? 0,
            'permitsCount' => (int) ($item->delivery_permits_count ?? 0),
            'deliveredAmount' => (float) ($item->delivery_permits_sum_delivery_amount ?? 0),
            'remaining' => (float) ($item->amount ?? 0) - (float) ($item->delivery_permits_sum_delivery_amount ?? 0),
            'date' => $item->request_date?->format('Y-m-d') ?? '—',
            'fiscalYear' => $item->fiscal_year ?: '—',
            'addedBy' => $item->createdBy?->name ?? '—',
            'createdById' => $item->created_by,
            'assignedToId' => $item->assigned_to,
            'assignedToName' => $item->assignedTo?->name ?? ($item->createdBy?->name ?? '—'),
            'ownerProcessedAt' => $item->owner_processed_at?->toIso8601String(),
            'actions' => $this->actionButtons($item->id),
        ];
    }

    /**
     * Build the pagination page list with ellipses.
     *
     * @return array<int, int|string>
     */
    protected function pageList(int $current, int $last): array
    {
        $pages = [];
        for ($p = 1; $p <= $last; $p++) {
            if (abs($p - $current) <= 2 || $p === 1 || $p === $last) {
                $pages[] = $p;
            } elseif (end($pages) !== '…') {
                $pages[] = '…';
            }
        }

        return $pages;
    }

    /**
     * Action buttons HTML for a row.
     */
    protected function actionButtons(int $id): string
    {
        $icon = fn (string $name, string $cls = 'h-4 w-4') => '<i data-lucide="' . $name . '" class="' . $cls . '"></i>';

        $permitBtn = '';
        $screens = auth()->user()?->permittedScreens();
        if ($screens === null || in_array('priced_delivery_permits', $screens, true)) {
            $permitBtn = '<button type="button" title="إضافة إذن تسعير" data-add-permit="' . $id . '" class="grid h-8 w-8 place-items-center rounded-lg border border-border text-muted-foreground transition-colors hover:border-gold/40 hover:bg-gold/10 hover:text-gold-foreground">' . $icon('receipt') . '</button>';
        }

        return
            '<div class="flex items-center gap-1">' .
            $permitBtn .
            '<button type="button" title="عرض" data-view="' . $id . '" class="grid h-8 w-8 place-items-center rounded-lg border border-border text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">' . $icon('eye') . '</button>' .
            '<button type="button" title="تعديل" data-edit="' . $id . '" class="grid h-8 w-8 place-items-center rounded-lg border border-border text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">' . $icon('pencil') . '</button>' .
            '<button type="button" title="حذف" data-delete="' . $id . '" data-delete-url="' . route('supply-requests.destroy', $id) . '" class="grid h-8 w-8 place-items-center rounded-lg border border-border text-muted-foreground transition-colors hover:border-destructive/30 hover:bg-destructive/10 hover:text-destructive">' . $icon('trash-2') . '</button>' .
            '</div>';
    }

    protected function generateSupplyNumber(): string
    {
        $prefix = 'SR-' . date('Y-m') . '-';
        $last = SupplyRequest::where('supply_request_number', 'like', $prefix . '%')
            ->orderByDesc('supply_request_number')
            ->value('supply_request_number');
        $seq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seq = ((int) end($parts)) + 1;
        }
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}