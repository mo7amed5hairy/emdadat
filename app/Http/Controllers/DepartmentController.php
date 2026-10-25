<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\Hospital;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class DepartmentController extends Controller
{
    public function index(Request $request): JsonResponse|RedirectResponse
    {
        if (!($request->wantsJson() || $request->ajax())) {
            return redirect()->route('hospitals.index');
        }

        $query = Department::query()->with('hospital')->orderBy('name');

        if ($term = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhereHas('hospital', fn ($h) => $h->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->boolean('export')) {
            return response()->json([
                'rows' => $query->get()->map(fn ($item) => $this->renderRow($item))->values(),
            ]);
        }

        $page = max(1, (int) $request->get('page', 1));
        $paginator = $query->paginate(8, ['*'], 'page', $page);

        return response()->json($this->tablePayload($paginator));
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $hospitalId = $request->input('hospital_id');
        $names = $request->input('names') ?: [$request->input('name')];
        $names = array_values(array_filter(array_map('trim', $names)));

        if (count($names) === 0) {
            return response()->json(['success' => false, 'message' => 'اسم القسم مطلوب.'], 422);
        }

        $created = [];
        $duplicates = [];

        foreach ($names as $name) {
            if (Department::where('hospital_id', $hospitalId)->where('name', $name)->exists()) {
                $duplicates[] = $name;
                continue;
            }
            $created[] = Department::create(['hospital_id' => $hospitalId, 'name' => $name]);
        }

        if (count($duplicates) > 0 && count($created) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'يوجد قسم بهذا الاسم داخل نفس المستشفى من قبل: ' . implode('، ', $duplicates),
            ], 422);
        }

        $count = count($created);
        $msg = $count === 1
            ? 'تم إضافة القسم بنجاح.'
            : "تم إضافة {$count} أقسام بنجاح.";

        if (count($duplicates) > 0) {
            $msg .= ' تم تخطي الأسماء المكررة: ' . implode('، ', $duplicates);
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
        ], 201);
    }

    /**
     * إضافة سريعة لقسم من داخل قوائم الاختيار (شاشات الطلبات) مرتبطًا بمستشفى محدد.
     */
    public function quickAdd(Request $request): JsonResponse
    {
        $hospitalId = (int) $request->input('hospital_id', 0);
        $name = trim((string) $request->input('name', ''));

        if ($name === '') {
            return response()->json(['success' => false, 'message' => 'اسم القسم مطلوب.'], 422);
        }

        if (! Hospital::whereKey($hospitalId)->exists()) {
            return response()->json(['success' => false, 'message' => 'المستشفى المختار غير موجود.'], 422);
        }

        $existing = Department::where('hospital_id', $hospitalId)->where('name', $name)->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'exists' => true,
                'message' => 'القسم موجود داخل هذا المستشفى من قبل وتم اختياره.',
                'item' => ['value' => $existing->id, 'label' => $existing->name],
            ]);
        }

        $department = Department::create(['hospital_id' => $hospitalId, 'name' => $name]);

        return response()->json([
            'success' => true,
            'exists' => false,
            'message' => 'تم إضافة القسم بنجاح.',
            'item' => ['value' => $department->id, 'label' => $department->name],
        ], 201);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $department->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث القسم بنجاح.',
            'row' => $this->renderRow($department->fresh()->load('hospital')),
        ]);
    }

    public function destroy(Department $department): JsonResponse
    {
        if ($department->supplyRequests()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف القسم لوجود طلبات إمداد مرتبطة به.',
            ], 422);
        }

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف القسم بنجاح.',
        ]);
    }

    public function byHospital(Request $request, int $hospitalId): JsonResponse
    {
        $departments = Department::where('hospital_id', $hospitalId)
            ->orderBy('name')
            ->pluck('name', 'id');

        return response()->json([
            'departments' => $departments->map(fn ($name, $id) => ['id' => $id, 'name' => $name])->values(),
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
            'grandTotal' => Department::count(),
            'hospitalCount' => Hospital::count(),
        ];
    }

    protected function renderRow(Department $department): array
    {
        return [
            'id' => $department->id,
            'name' => $department->name,
            'hospital' => $department->hospital->name ?? '—',
            'hospitalId' => $department->hospital_id,
            'actions' => $this->actionButtons($department),
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

    protected function actionButtons(Department $department): string
    {
        $icon = fn (string $name, string $cls = 'h-4 w-4') => '<i data-lucide="' . $name . '" class="' . $cls . '"></i>';

        return
            '<div class="flex items-center gap-1">' .
            '<button type="button" title="تعديل" data-edit="' . $department->id . '" class="grid h-8 w-8 place-items-center rounded-lg border border-border text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">' . $icon('pencil') . '</button>' .
            '<button type="button" title="حذف" data-delete-url="' . route('departments.destroy', $department->id) . '" class="grid h-8 w-8 place-items-center rounded-lg border border-border text-muted-foreground transition-colors hover:border-destructive/30 hover:bg-destructive/10 hover:text-destructive">' . $icon('trash-2') . '</button>' .
            '</div>';
    }
}