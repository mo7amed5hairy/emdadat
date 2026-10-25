<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = User::query()->orderBy('name');

        if ($term = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('username', 'like', "%{$term}%");
            });
        }

        if ($request->boolean('export')) {
            return response()->json([
                'rows' => $query->get()->map(fn ($item) => $this->renderRow($item))->values(),
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            $page = max(1, (int) $request->get('page', 1));
            $perPage = 10;

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json($this->tablePayload($paginator));
        }

        $users = User::orderBy('name')->get();
        $hospitals = Hospital::with('departments')->orderBy('name')->get();

        return view('users.index', compact('users', 'hospitals'));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => $data['password'],
            'is_admin' => (bool) ($data['is_admin'] ?? false),
            'category' => $data['category'] ?? null,
            'hospital_id' => $data['hospital_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'input_permissions' => $data['input_permissions'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المستخدم بنجاح.',
            'user' => $this->renderRow($user),
        ], 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        $user->name = $data['name'];
        $user->username = $data['username'];
        $user->is_admin = (bool) ($data['is_admin'] ?? false);
        $user->category = $data['category'] ?? null;
        $user->hospital_id = $data['hospital_id'] ?? null;
        $user->department_id = $data['department_id'] ?? null;
        $user->input_permissions = $data['input_permissions'] ?? null;

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        SupplyRequest::where('created_by', $user->id)
            ->update(['request_type' => $user->category]);

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل بيانات المستخدم بنجاح.',
            'user' => $this->renderRow($user),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك حذف حسابك الشخصي.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المستخدم بنجاح.',
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
        ];
    }

    protected function renderRow(User $item): array
    {
        $selfId = auth()->id();
        $canManage = auth()->user()->is_admin;

        return [
            'id' => $item->id,
            'name' => $item->name,
            'username' => $item->username,
            'isAdmin' => $item->is_admin,
            'category' => $item->category,
            'hospitalId' => $item->hospital_id,
            'departmentId' => $item->department_id,
            'inputPermissions' => $item->input_permissions,
            'isSelf' => $item->id === $selfId,
            'manageable' => $canManage,
            'actions' => $this->actionButtons($item->id, $item->id === $selfId),
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

    protected function actionButtons(int $id, bool $isSelf): string
    {
        $icon = fn (string $name, string $cls = 'h-4 w-4') => '<i data-lucide="' . $name . '" class="' . $cls . '"></i>';

        $edit = '<button type="button" title="تعديل" data-edit="' . $id . '" class="grid h-8 w-8 place-items-center rounded-lg border border-border text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">' . $icon('pencil') . '</button>';

        $delete = $isSelf
            ? '<button type="button" title="لا يمكن حذف حسابك" disabled class="grid h-8 w-8 cursor-not-allowed place-items-center rounded-lg border border-border text-muted-foreground/40">' . $icon('trash-2') . '</button>'
            : '<button type="button" title="حذف" data-delete="' . $id . '" data-delete-url="' . route('users.destroy', $id) . '" class="grid h-8 w-8 place-items-center rounded-lg border border-border text-muted-foreground transition-colors hover:border-destructive/30 hover:bg-destructive/10 hover:text-destructive">' . $icon('trash-2') . '</button>';

        return '<div class="flex items-center gap-1">' . $edit . $delete . '</div>';
    }
}
