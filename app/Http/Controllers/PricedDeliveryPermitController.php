<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePricedDeliveryPermitRequest;
use App\Http\Requests\UpdatePricedDeliveryPermitRequest;
use App\Models\Company;
use App\Models\PricedDeliveryPermit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class PricedDeliveryPermitController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = PricedDeliveryPermit::query()
            ->with(['company', 'createdBy'])
            ->latest('id')
            ->visibleTo(auth()->user());

        if ($term = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($term) {
                $q->where('delivery_permit_number', 'like', "%{$term}%")
                    ->orWhere('supply_request_number', 'like', "%{$term}%")
                    ->orWhere('status', 'like', "%{$term}%")
                    ->orWhereHas('company', fn ($c) => $c->where('name', 'like', "%{$term}%"))
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

        $companies = Company::visibleTo(auth()->user())->orderBy('name')->get();
        $permittedFields = auth()->user()->permittedInputFields('priced_delivery_permits');
        $users = User::where('is_admin', false)->orderBy('name')->get();

        return view('priced-delivery-permits.index', compact('companies', 'permittedFields', 'users'));
    }

    public function store(StorePricedDeliveryPermitRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = auth()->user();
        $permitted = $user->permittedInputFields('priced_delivery_permits');

        if ($permitted !== null) {
            if (! in_array('delivery_permit_number', $permitted, true) || empty($data['delivery_permit_number'])) {
                $data['delivery_permit_number'] = $this->generatePermitNumber();
            }
            if (! in_array('company_id', $permitted, true)) {
                $data['company_id'] = null;
            }
            if (! in_array('status', $permitted, true)) {
                $data['status'] = 'لم يتم الصرف';
            }
            if (! in_array('supply_request_number', $permitted, true)) {
                $data['supply_request_number'] = null;
            }
        }

        $data['created_by'] = $user->id;
        $data['assigned_to'] = ! empty($data['assigned_to']) ? (int) $data['assigned_to'] : $user->id;

        $permit = PricedDeliveryPermit::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة إذن التسليم المسعّر بنجاح.',
            'request' => $this->renderRow($permit),
        ], 201);
    }

    public function update(UpdatePricedDeliveryPermitRequest $request, PricedDeliveryPermit $permit): JsonResponse
    {
        $data = $request->validated();
        $user = auth()->user();
        $permitted = $user->permittedInputFields('priced_delivery_permits');

        if (! empty($data['assigned_to'])) {
            $data['assigned_to'] = (int) $data['assigned_to'];
        } else {
            unset($data['assigned_to']);
        }
        $data['created_by'] = $permit->created_by ?? $user->id;

        if ($permitted !== null) {
            if (! in_array('delivery_permit_number', $permitted, true)) {
                unset($data['delivery_permit_number']);
            }
            if (! in_array('company_id', $permitted, true)) {
                unset($data['company_id']);
            }
            if (! in_array('status', $permitted, true)) {
                unset($data['status']);
            }
            if (! in_array('supply_request_number', $permitted, true)) {
                unset($data['supply_request_number']);
            }
        }

        $permit->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل إذن التسليم بنجاح.',
            'request' => $this->renderRow($permit->fresh()),
        ]);
    }

    public function destroy(PricedDeliveryPermit $permit): JsonResponse
    {
        $permit->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف إذن التسليم بنجاح.',
        ]);
    }

    protected function tablePayload(LengthAwarePaginator $paginator): array
    {
        $rows = $paginator->getCollection()->map(fn ($item) => $this->renderRow($item));

        return [
            'rows' => $rows,
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'perPage' => $paginator->perPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'pages' => $this->pageList($paginator->currentPage(), $paginator->lastPage()),
            'grandTotal' => PricedDeliveryPermit::visibleTo(auth()->user())->sum('delivery_amount'),
            'doneCount' => PricedDeliveryPermit::visibleTo(auth()->user())->where('status', 'تم الصرف')->count(),
            'pendingCount' => PricedDeliveryPermit::visibleTo(auth()->user())->where('status', '!=', 'تم الصرف')->count(),
        ];
    }

    protected function renderRow(PricedDeliveryPermit $item): array
    {
        return [
            'id' => $item->id,
            'amount' => $item->delivery_amount ?? 0,
            'supplyNo' => $item->supply_request_number ?? '—',
            'noteNo' => $item->delivery_permit_number ?? '—',
            'issueDate' => $item->issue_date?->format('Y-m-d') ?? '—',
            'companyId' => $item->company_id,
            'company' => $item->company?->name ?? '—',
            'formDate' => $item->form_date?->format('Y-m-d') ?? '—',
            'status' => $item->status ?? '—',
            'fullyDelivered' => (bool) $item->fully_delivered,
            'addedBy' => $item->createdBy?->name ?? '—',
            'createdById' => $item->created_by,
            'assignedToId' => $item->assigned_to,
            'assignedToName' => $item->assignedTo?->name ?? ($item->createdBy?->name ?? '—'),
            'actions' => $this->actionButtons($item->id),
        ];
    }

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

    protected function actionButtons(int $id): string
    {
        $icon = fn (string $name, string $cls = 'h-4 w-4') => '<i data-lucide="' . $name . '" class="' . $cls . '"></i>';

        return
            '<div class="flex items-center gap-1">' .
            '<button type="button" title="عرض" data-view="' . $id . '" class="grid h-8 w-8 place-items-center rounded-lg border border-border text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">' . $icon('eye') . '</button>' .
            '<button type="button" title="تعديل" data-edit="' . $id . '" class="grid h-8 w-8 place-items-center rounded-lg border border-border text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">' . $icon('pencil') . '</button>' .
            '<button type="button" title="حذف" data-delete="' . $id . '" data-delete-url="' . route('priced-delivery-permits.destroy', $id) . '" class="grid h-8 w-8 place-items-center rounded-lg border border-border text-muted-foreground transition-colors hover:border-destructive/30 hover:bg-destructive/10 hover:text-destructive">' . $icon('trash-2') . '</button>' .
            '</div>';
    }

    protected function generatePermitNumber(): string
    {
        $prefix = 'DN-' . date('Y-m') . '-';
        $last = PricedDeliveryPermit::where('delivery_permit_number', 'like', $prefix . '%')
            ->orderByDesc('delivery_permit_number')
            ->value('delivery_permit_number');
        $seq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seq = ((int) end($parts)) + 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}