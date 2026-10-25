@extends('layouts.app')

@section('title', 'الشركات')
@section('description', 'إدارة الشركات الموردة وفئاتها مع الإضافة والتعديل والحذف والفلترة والتصدير.')

@section('page-title', 'الشركات')
@section('page-subtitle', 'إدارة الشركات الموردة وفئة كل شركة')

@section('content')
    @php
        $companyTotal = \App\Models\Company::count();
    @endphp

    <!-- بطاقة الإحصائية -->
    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand/10 text-primary">
                <i data-lucide="factory" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">إجمالي الشركات</p>
                <p id="stat-companies" class="text-lg font-extrabold text-foreground">{{ number_format($companyTotal) }}</p>
            </div>
        </div>
    </div>

    <!-- رأس الجدول وزر الإضافة -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="flex items-center gap-2 text-sm font-bold text-foreground">
            <i data-lucide="factory" class="h-4 w-4 text-primary"></i>
            قائمة الشركات
        </h2>
        <button type="button" id="add-btn"
            class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand px-4 text-[13px] font-bold text-primary-foreground shadow-soft transition-transform hover:-translate-y-0.5">
            <i data-lucide="plus" class="h-4 w-4"></i>
            إضافة شركة
        </button>
    </div>

    <!-- الجدول -->
    <div id="companies-table"></div>

    <!-- ===== مودال إضافة/تعديل شركة ===== -->
    <div id="company-modal" class="fixed inset-0 z-50 items-start justify-center overflow-y-auto bg-foreground/45 p-4 backdrop-blur-sm" style="display:none" onclick="window.emdadat.closeOnBackdrop('company-modal', event)">
        <div class="animate-zoom-in my-8 w-full max-w-md overflow-hidden rounded-2xl border border-border bg-card shadow-card">
            <div class="flex items-center justify-between gap-4 bg-brand px-5 py-4 text-primary-foreground">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-primary-foreground/15">
                        <i data-lucide="factory" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h3 id="company-modal-title" class="text-base font-bold">إضافة شركة</h3>
                        <p class="text-xs opacity-80">قم بإدخال بيانات الشركة الجديدة</p>
                    </div>
                </div>
                <button type="button" onclick="window.emdadat.closeModal('company-modal')" class="grid h-9 w-9 place-items-center rounded-lg bg-primary-foreground/15 transition-colors hover:bg-primary-foreground/25" aria-label="إغلاق">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form id="company-form" class="p-5">
                <div class="space-y-4">
                    <div class="field-wrap space-y-1.5">
                        <label class="block text-[13px] font-semibold text-foreground/85">اسم الشركة <span class="text-destructive">*</span></label>
                        <input id="company-name" name="name" type="text" placeholder="مثال: شركة النيل للأدوية"
                            class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                    </div>

                    <div class="field-wrap space-y-1.5">
                        <label class="block text-[13px] font-semibold text-foreground/85">فئة الشركة <span class="text-destructive">*</span></label>
                        <div class="relative">
                            <select id="company-category" name="category"
                                class="h-10 w-full appearance-none rounded-lg border border-input bg-card ps-3 pe-9 text-[13px] text-foreground outline-none transition-shadow focus:border-primary focus:ring-2 focus:ring-ring/35">
                                <option value="">اختر...</option>
                                <option value="مستلزمات">مستلزمات</option>
                                <option value="أدوية">أدوية</option>
                                <option value="كيماويات">كيماويات</option>
                            </select>
                            <i data-lucide="chevron-down" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-end gap-2 border-t border-border pt-4">
                    <button type="button" onclick="window.emdadat.closeModal('company-modal')" class="h-10 rounded-lg border border-border bg-card px-5 text-[13px] font-bold text-muted-foreground transition-colors hover:bg-accent">إلغاء</button>
                    <button type="submit" id="company-save-btn" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand px-5 text-[13px] font-bold text-primary-foreground shadow-soft transition-transform hover:-translate-y-0.5">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        حفظ
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.emdadat_data = {
            loadUrl: "{{ route('companies.index') }}",
        };
    </script>
@endsection

@push('scripts')
    <script>
        (function () {
            const D = window.emdadat_data;
            if (!D) return;
            const boot = function () {
            const E = window.emdadat;
            if (!E) return;
            let currentTable = null;
            let editing = null;

            function updateStats() {
                const total = document.getElementById("stat-companies");
                if (!total) return;
                const payload = currentTable && currentTable._lastPayload;
                if (payload) total.textContent = Number(payload.totalCount).toLocaleString('en-US');
            }

            const columns = [
                { key: "idx", header: "مسلسل", width: "70px", render: (r, i, p) => (p.from || 1) + i },
                { key: "name", header: "اسم الشركة", render: (r) => `<span class="font-semibold text-foreground">${r.name}</span>` },
                { key: "category", header: "فئة الشركة", render: (r) => `<span class="rounded-md bg-accent px-2 py-1 text-[12px] font-semibold text-accent-foreground">${r.category || '—'}</span>` },
                { key: "actions", header: "الإجراءات", width: "140px", render: (r) => r.actions },
            ];

            function renderTable() {
                currentTable = E.renderServerTable(document.getElementById("companies-table"), columns, {
                    loadUrl: D.loadUrl,
                    exportUrl: D.loadUrl,
                    exportName: "الشركات",
                    searchable: true,
                    minWidth: "",
                    onLoaded: (payload) => {
                        currentTable._lastPayload = payload;
                        updateStats();
                        window.lucide.createIcons();
                    },
                });
            }

            document.getElementById("add-btn").addEventListener("click", () => {
                editing = null;
                document.getElementById("company-modal-title").textContent = "إضافة شركة";
                document.getElementById("company-save-btn").innerHTML = '<i data-lucide="save" class="h-4 w-4"></i> حفظ';
                document.getElementById("company-form").reset();
                E.clearFieldErrors(document.getElementById("company-form"));
                E.openModal("company-modal");
            });

            document.addEventListener("click", (e) => {
                const btn = e.target.closest('#companies-table [data-edit]');
                if (!btn) return;
                const id = btn.getAttribute("data-edit");
                const payload = currentTable && currentTable._lastPayload;
                const row = payload && payload.rows.find(r => String(r.id) === String(id));
                if (!row) return;
                editing = id;
                document.getElementById("company-modal-title").textContent = "تعديل الشركة";
                document.getElementById("company-save-btn").innerHTML = '<i data-lucide="save" class="h-4 w-4"></i> حفظ التعديلات';
                document.getElementById("company-name").value = row.name;
                document.getElementById("company-category").value = row.category || "";
                E.clearFieldErrors(document.getElementById("company-form"));
                E.openModal("company-modal");
            });

            document.getElementById("company-form").addEventListener("submit", async (e) => {
                e.preventDefault();
                const form = e.currentTarget;
                E.clearFieldErrors(form);
                const btn = document.getElementById("company-save-btn");
                btn.disabled = true;
                const original = btn.innerHTML;
                btn.innerHTML = '<span class="spinner"></span> جارٍ الحفظ...';
                const data = {
                    name: document.getElementById("company-name").value.trim(),
                    category: document.getElementById("company-category").value,
                };

                try {
                    const url = editing
                        ? "{{ route('companies.update', ':id') }}".replace(':id', editing)
                        : D.loadUrl;
                    const res = await E.request(url, { method: editing ? "PUT" : "POST", body: data });
                    if (res.ok === false) {
                        if (res.errors && res.errors.name) E.showFieldError("company-name", res.errors.name[0]);
                        if (res.errors && res.errors.category) E.showFieldError("company-category", res.errors.category[0]);
                        if (res.message) E.toast(res.message, "error");
                        return;
                    }
                    E.toast(res.data.message || "تم الحفظ بنجاح", "success");
                    E.closeModal("company-modal");
                    document.getElementById("company-form").reset();
                    renderTable();
                } catch (err) {
                    if (!/Session expired/.test(err.message)) E.toast(err.message || "تعذر الحفظ", "error");
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = original;
                }
            });

            E.initDeleteHandlers(() => currentTable);
            renderTable();
            };
            document.addEventListener('DOMContentLoaded', boot);
        })();
    </script>
@endpush
