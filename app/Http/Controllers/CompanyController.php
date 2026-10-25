<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = Company::query()->visibleTo(auth()->user())->orderBy('name');

        if ($term = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('category', 'like', "%{$term}%");
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

        return view('companies.index');
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = Company::create(
            array_merge($request->validated(), ['created_by' => auth()->id()])
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الشركة بنجاح.',
            'row' => $this->renderRow($company),
        ], 201);
    }

    /**
     * إضافة سريعة لشركة من داخل قوائم الاختيار (شاشة أذون التسليم المسعّرة).
     */
    public function quickAdd(Request $request): JsonResponse
    {
        $name = trim((string) $request->input('name', ''));
        $category = trim((string) $request->input('category', ''));

        if ($name === '') {
            return response()->json(['success' => false, 'message' => 'اسم الشركة مطلوب.'], 422);
        }

        $existing = Company::visibleTo(auth()->user())->where('name', $name)->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'exists' => true,
                'message' => 'الشركة موجودة من قبل وتم اختيارها.',
                'item' => ['value' => $existing->id, 'label' => $existing->name],
            ]);
        }

        $company = Company::create([
            'name' => $name,
            'category' => $category ?: null,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'exists' => false,
            'message' => 'تم إضافة الشركة بنجاح.',
            'item' => ['value' => $company->id, 'label' => $company->name],
        ], 201);
    }

    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $company->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الشركة بنجاح.',
            'row' => $this->renderRow($company->fresh()),
        ]);
    }

    public function destroy(Company $company): JsonResponse
    {
        if ($company->pricedDeliveryPermits()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف الشركة لوجود أذون تسليم مرتبطة بها.',
            ], 422);
        }

        $company->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الشركة بنجاح.',
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
            'totalCount' => Company::visibleTo(auth()->user())->count(),
        ];
    }

    protected function renderRow(Company $company): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'category' => $company->category,
            'actions' => $this->actionButtons($company),
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

    protected function actionButtons(Company $company): string
    {
        $icon = fn (string $name, string $cls = 'h-4 w-4') => '<i data-lucide="' . $name . '" class="' . $cls . '"></i>';

        return
            '<div class="flex items-center gap-1">' .
            '<button type="button" title="تعديل" data-edit="' . $company->id . '" class="grid h-8 w-8 place-items-center rounded-lg border border-border text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">' . $icon('pencil') . '</button>' .
            '<button type="button" title="حذف" data-delete-url="' . route('companies.destroy', $company->id) . '" class="grid h-8 w-8 place-items-center rounded-lg border border-border text-muted-foreground transition-colors hover:border-destructive/30 hover:bg-destructive/10 hover:text-destructive">' . $icon('trash-2') . '</button>' .
            '</div>';
    }
}
