<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFinancialLinkRequest;
use App\Http\Requests\UpdateFinancialLinkRequest;
use App\Models\FinancialLink;
use App\Models\FinancialLinkType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class FinancialLinkController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = FinancialLink::query()
            ->with('types')
            ->orderBy('name');

        if ($term = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhereHas('types', fn ($t) => $t->where('name', 'like', "%{$term}%"));
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

        return view('financial-links.index');
    }

    public function store(StoreFinancialLinkRequest $request): JsonResponse
    {
        $link = FinancialLink::create(
            array_merge($request->validated(), ['created_by' => auth()->id()])
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الارتباط المالي بنجاح.',
            'row' => $this->renderRow($link),
        ], 201);
    }

    public function update(UpdateFinancialLinkRequest $request, FinancialLink $financialLink): JsonResponse
    {
        $financialLink->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الارتباط المالي بنجاح.',
            'row' => $this->renderRow($financialLink->fresh()),
        ]);
    }

    public function destroy(FinancialLink $financialLink): JsonResponse
    {
        $financialLink->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الارتباط المالي بنجاح.',
        ]);
    }

    /* ---------- أنواع الارتباط المالي ---------- */

    public function typeStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'financial_link_id' => ['required', 'exists:financial_links,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $type = FinancialLinkType::create(
            array_merge($data, ['created_by' => auth()->id()])
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة نوع الارتباط المالي بنجاح.',
            'row' => $this->renderTypeRow($type->load('financialLink')),
        ], 201);
    }

    public function typeUpdate(Request $request, FinancialLinkType $type): JsonResponse
    {
        $data = $request->validate([
            'financial_link_id' => ['required', 'exists:financial_links,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $type->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث نوع الارتباط المالي بنجاح.',
            'row' => $this->renderTypeRow($type->fresh('financialLink')),
        ]);
    }

    public function typeDestroy(FinancialLinkType $type): JsonResponse
    {
        $type->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف نوع الارتباط المالي بنجاح.',
        ]);
    }

    public function typeIndex(Request $request): JsonResponse
    {
        $query = FinancialLinkType::query()
            ->with('financialLink')
            ->orderByDesc('id');

        if ($term = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhereHas('financialLink', fn ($l) => $l->where('name', 'like', "%{$term}%"));
            });
        }

        $types = $query->get();

        return response()->json([
            'rows' => $types->map(fn ($item) => $this->renderTypeRow($item))->values(),
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
            'usedCount' => FinancialLink::count(),
        ];
    }

    protected function renderRow(FinancialLink $link): array
    {
        $typesCount = $link->types_count ?? $link->types->count();

        return [
            'id' => $link->id,
            'name' => $link->name,
            'typesCount' => $typesCount,
            'actions' => $this->actionButtons($link),
        ];
    }

    protected function renderTypeRow(FinancialLinkType $type): array
    {
        return [
            'id' => $type->id,
            'name' => $type->name,
            'linkId' => $type->financial_link_id,
            'linkName' => $type->financialLink->name ?? '—',
            'actions' => $this->typeActionButtons($type),
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

    protected function actionButtons(FinancialLink $link): string
    {
        $icon = fn (string $name, string $cls = 'h-4 w-4') => '<i data-lucide="' . $name . '" class="' . $cls . '"></i>';

        return
            '<div class="flex items-center gap-1">' .
            '<button type="button" title="تعديل" data-edit="' . $link->id . '" class="grid h-8 w-8 place-items-center rounded-lg border border-border text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">' . $icon('pencil') . '</button>' .
            '<button type="button" title="حذف" data-delete-url="' . route('financial-links.destroy', $link->id) . '" class="grid h-8 w-8 place-items-center rounded-lg border border-border text-muted-foreground transition-colors hover:border-destructive/30 hover:bg-destructive/10 hover:text-destructive">' . $icon('trash-2') . '</button>' .
            '</div>';
    }

    protected function typeActionButtons(FinancialLinkType $type): string
    {
        $icon = fn (string $name, string $cls = 'h-4 w-4') => '<i data-lucide="' . $name . '" class="' . $cls . '"></i>';

        return
            '<div class="flex items-center gap-1">' .
            '<button type="button" title="تعديل" data-edit-type="' . $type->id . '" class="grid h-8 w-8 place-items-center rounded-lg border border-border text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">' . $icon('pencil') . '</button>' .
            '<button type="button" title="حذف" data-delete-type-url="' . route('financial-link-types.destroy', $type->id) . '" class="grid h-8 w-8 place-items-center rounded-lg border border-border text-muted-foreground transition-colors hover:border-destructive/30 hover:bg-destructive/10 hover:text-destructive">' . $icon('trash-2') . '</button>' .
            '</div>';
    }
}