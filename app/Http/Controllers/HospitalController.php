<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHospitalRequest;
use App\Http\Requests\UpdateHospitalRequest;
use App\Models\Department;
use App\Models\Hospital;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class HospitalController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = Hospital::query()->withCount('departments')->orderBy('name');

        if ($term = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhereHas('departments', fn ($d) => $d->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->boolean('export')) {
            return response()->json([
                'rows' => $query->get()->map(fn ($item) => $this->renderRow($item))->values(),
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            $page = max(1, (int) $request->get('page', 1));
            $paginator = $query->paginate(8, ['*'], 'page', $page);

            return response()->json($this->tablePayload($paginator));
        }

        return view('hospitals.index');
    }

    public function store(StoreHospitalRequest $request): JsonResponse
    {
        $names = $request->input('names') ?: [$request->input('name')];
        $names = array_values(array_filter(array_map('trim', $names)));

        if (count($names) === 0) {
            return response()->json(['success' => false, 'message' => 'اسم المستشفى مطلوب.'], 422);
        }

        $created = [];
        $duplicates = [];

        foreach ($names as $name) {
            if (Hospital::where('name', $name)->exists()) {
                $duplicates[] = $name;
                continue;
            }
            $created[] = Hospital::create(['name' => $name]);
        }

        if (count($duplicates) > 0 && count($created) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'اسم المستشفى مستخدم من قبل: ' . implode('، ', $duplicates),
            ], 422);
        }

        $count = count($created);
        $msg = $count === 1
            ? 'تم إضافة المستشفى بنجاح.'
            : "تم إضافة {$count} مستشفى بنجاح.";

        if (count($duplicates) > 0) {
            $msg .= ' تم تخطي الأسماء المكررة: ' . implode('، ', $duplicates);
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
        ], 201);
    }

    /**
     * إضافة سريعة لمستشفى واحد من داخل قوائم الاختيار (شاشات الطلبات).
     */
    public function quickAdd(Request $request): JsonResponse
    {
        $name = trim((string) $request->input('name', ''));

        if ($name === '') {
            return response()->json(['success' => false, 'message' => 'اسم المستشفى مطلوب.'], 422);
        }

        $existing = Hospital::where('name', $name)->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'exists' => true,
                'message' => 'المستشفى موجود من قبل وتم اختياره.',
                'item' => ['value' => $existing->id, 'label' => $existing->name],
            ]);
        }

        $hospital = Hospital::create(['name' => $name]);

        return response()->json([
            'success' => true,
            'exists' => false,
            'message' => 'تم إضافة المستشفى بنجاح.',
            'item' => ['value' => $hospital->id, 'label' => $hospital->name],
        ], 201);
    }

    public function update(UpdateHospitalRequest $request, Hospital $hospital): JsonResponse
    {
        $hospital->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المستشفى بنجاح.',
            'row' => $this->renderRow($hospital->fresh()->loadCount('departments')),
        ]);
    }

    public function destroy(Hospital $hospital): JsonResponse
    {
        if ($hospital->departments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف المستشفى لوجود أقسام مرتبطة به.',
            ], 422);
        }

        if ($hospital->supplyRequests()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف المستشفى لوجود طلبات إمداد مرتبطة به.',
            ], 422);
        }

        $hospital->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المستشفى بنجاح.',
        ]);
    }

    /**
     * Build the JSON payload for the hospitals API used by the DataTable.
     */
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
            'grandTotal' => Hospital::count(),
            'departmentCount' => Department::count(),
        ];
    }

    /**
     * Render a single hospital row for the DataTable body.
     */
    protected function renderRow(Hospital $hospital): array
    {
        return [
            'id' => $hospital->id,
            'name' => $hospital->name,
            'departments' => $hospital->departments_count,
            'actions' => $this->actionButtons($hospital),
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
     * Action buttons HTML for a hospital row.
     */
    protected function actionButtons(Hospital $hospital): string
    {
        $icon = fn (string $name, string $cls = 'h-4 w-4') => '<i data-lucide="' . $name . '" class="' . $cls . '"></i>';

        return
            '<div class="flex items-center gap-1">' .
            '<button type="button" title="تعديل" data-edit="' . $hospital->id . '" class="grid h-8 w-8 place-items-center rounded-lg border border-border text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">' . $icon('pencil') . '</button>' .
            '<button type="button" title="حذف" data-delete-url="' . route('hospitals.destroy', $hospital->id) . '" class="grid h-8 w-8 place-items-center rounded-lg border border-border text-muted-foreground transition-colors hover:border-destructive/30 hover:bg-destructive/10 hover:text-destructive">' . $icon('trash-2') . '</button>' .
            '</div>';
    }
}