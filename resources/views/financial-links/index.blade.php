@extends('layouts.app')

@section('title', 'الارتباط المالي')
@section('description', 'إدارة الارتباطات المالية وأنواعها المستخدمة في طلبات الإمداد مع الإضافة والتعديل والحذف.')

@section('page-title', 'الارتباط المالي')
@section('page-subtitle', 'إدارة الارتباطات المالية وأنواعها التي تظهر في شاشة إضافة طلب الإمداد')

@section('content')
    @php
        $linkTotal = \App\Models\FinancialLink::count();
    @endphp

    <!-- بطاقة الإحصائية -->
    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand/10 text-primary">
                <i data-lucide="link" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">إجمالي الارتباطات المالية</p>
                <p id="stat-links" class="text-lg font-extrabold text-foreground">{{ number_format($linkTotal) }}</p>
            </div>
        </div>
    </div>

    <!-- رأس الجدول وزر الإضافة -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="flex items-center gap-2 text-sm font-bold text-foreground">
            <i data-lucide="link" class="h-4 w-4 text-primary"></i>
            قائمة الارتباطات المالية
        </h2>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" id="add-type-btn"
                class="inline-flex h-9 items-center gap-2 rounded-lg border border-brand/40 bg-brand/5 px-4 text-[13px] font-bold text-primary transition-colors hover:bg-brand/10">
                <i data-lucide="plus" class="h-4 w-4"></i>
                إضافة نوع ارتباط مالي
            </button>
            <button type="button" id="add-btn"
                class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand px-4 text-[13px] font-bold text-primary-foreground shadow-soft transition-transform hover:-translate-y-0.5">
                <i data-lucide="plus" class="h-4 w-4"></i>
                إضافة ارتباط مالي
            </button>
        </div>
    </div>

    <!-- الجدول -->
    <div id="links-table"></div>

    <!-- ===== مودال إضافة/تعديل ارتباط مالي ===== -->
    <div id="link-modal" class="fixed inset-0 z-50 items-start justify-center overflow-y-auto bg-foreground/45 p-4 backdrop-blur-sm" style="display:none" onclick="window.emdadat.closeOnBackdrop('link-modal', event)">
        <div class="animate-zoom-in my-8 w-full max-w-md rounded-2xl border border-border bg-card shadow-card">
            <div class="flex items-center justify-between gap-4 rounded-t-2xl bg-brand px-5 py-4 text-primary-foreground">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-primary-foreground/15">
                        <i data-lucide="link" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h3 id="link-modal-title" class="text-base font-bold">إضافة ارتباط مالي</h3>
                        <p class="text-xs opacity-80">قم بإدخال اسم الارتباط المالي الجديد</p>
                    </div>
                </div>
                <button type="button" onclick="window.emdadat.closeModal('link-modal')" class="grid h-9 w-9 place-items-center rounded-lg bg-primary-foreground/15 transition-colors hover:bg-primary-foreground/25" aria-label="إغلاق">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form id="link-form" class="p-5">
                <div class="field-wrap space-y-1.5">
                    <label class="block text-[13px] font-semibold text-foreground/85">اسم الارتباط المالي <span class="text-destructive">*</span></label>
                    <input id="link-name" name="name" type="text" placeholder="مثال: ميزانية"
                        class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-end gap-2 rounded-b-2xl border-t border-border bg-card pt-4">
                    <button type="button" onclick="window.emdadat.closeModal('link-modal')" class="h-10 rounded-lg border border-border bg-card px-5 text-[13px] font-bold text-muted-foreground transition-colors hover:bg-accent">إلغاء</button>
                    <button type="submit" id="link-save-btn" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand px-5 text-[13px] font-bold text-primary-foreground shadow-soft transition-transform hover:-translate-y-0.5">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        حفظ الارتباط المالي
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== مودال إضافة/تعديل نوع ارتباط مالي ===== -->
    <div id="type-modal" class="fixed inset-0 z-50 items-start justify-center overflow-y-auto bg-foreground/45 p-4 backdrop-blur-sm" style="display:none" onclick="window.emdadat.closeOnBackdrop('type-modal', event)">
        <div class="animate-zoom-in my-8 w-full max-w-md rounded-2xl border border-border bg-card shadow-card">
            <div class="flex items-center justify-between gap-4 rounded-t-2xl bg-brand px-5 py-4 text-primary-foreground">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-primary-foreground/15">
                        <i data-lucide="tag" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h3 id="type-modal-title" class="text-base font-bold">إضافة نوع ارتباط مالي</h3>
                        <p class="text-xs opacity-80">قم بإدخال نوع جديد ضمن الارتباط المالي المختار</p>
                    </div>
                </div>
                <button type="button" onclick="window.emdadat.closeModal('type-modal')" class="grid h-9 w-9 place-items-center rounded-lg bg-primary-foreground/15 transition-colors hover:bg-primary-foreground/25" aria-label="إغلاق">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form id="type-form" class="p-5">
                <div class="field-wrap space-y-1.5">
                    <label class="block text-[13px] font-semibold text-foreground/85">الارتباط المالي <span class="text-destructive">*</span></label>
                    <div id="type-link-select"></div>
                </div>
                <div class="field-wrap space-y-1.5 mt-4">
                    <label class="block text-[13px] font-semibold text-foreground/85">نوع الارتباط المالي <span class="text-destructive">*</span></label>
                    <input id="type-name" name="name" type="text" placeholder="مثال: مستلزمات طبية"
                        class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-end gap-2 rounded-b-2xl border-t border-border bg-card pt-4">
                    <button type="button" onclick="window.emdadat.closeModal('type-modal')" class="h-10 rounded-lg border border-border bg-card px-5 text-[13px] font-bold text-muted-foreground transition-colors hover:bg-accent">إلغاء</button>
                    <button type="submit" id="type-save-btn" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand px-5 text-[13px] font-bold text-primary-foreground shadow-soft transition-transform hover:-translate-y-0.5">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        حفظ النوع
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.emdadat_data = {
            loadUrl: "{{ route('financial-links.index') }}",
            storeUrl: "{{ route('financial-links.store') }}",
            typesUrl: "{{ route('financial-link-types.index') }}",
            typeStoreUrl: "{{ route('financial-link-types.store') }}",
            links: {!! \App\Models\FinancialLink::orderBy('name')->get(['id', 'name'])->map(fn ($l) => ['value' => $l->id, 'label' => $l->name])->toJson() !!},
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
            let editingType = null;
            let typeLinkSelectRef = null;

            function updateStats() {
                const total = document.getElementById("stat-links");
                if (!total) return;
                const payload = currentTable && currentTable._lastPayload;
                if (payload) total.textContent = Number(payload.usedCount).toLocaleString('en-US');
            }

            const columns = [
                { key: "idx", header: "مسلسل", width: "70px", render: (r, i, p) => (p.from || 1) + i },
                { key: "name", header: "الارتباط المالي", render: (r) => `<span class="font-semibold">${r.name}</span>` },
                { key: "typesCount", header: "عدد الأنواع", render: (r) => `<span class="rounded-md bg-accent px-2 py-1 text-[12px] font-semibold text-accent-foreground">${Number(r.typesCount || 0).toLocaleString('en-US')}</span>` },
                { key: "actions", header: "الإجراءات", width: "140px", render: (r) => r.actions },
            ];

            function renderTable() {
                currentTable = E.renderServerTable(document.getElementById("links-table"), columns, {
                    loadUrl: D.loadUrl,
                    exportName: "الارتباطات المالية",
                    searchable: true,
                    minWidth: "",
                    onLoaded: (payload) => {
                        currentTable._lastPayload = payload;
                        updateStats();
                        window.lucide.createIcons();
                    },
                });
            }

            function buildTypeLinkSelect(value) {
                typeLinkSelectRef = E.createSearchableSelect(document.getElementById("type-link-select"), D.links, {
                    placeholder: "ابحث واختر الارتباط المالي...",
                    value: value ? String(value) : "",
                });
            }

            function resetTypeForm() {
                editingType = null;
                document.getElementById("type-modal-title").textContent = "إضافة نوع ارتباط مالي";
                document.getElementById("type-save-btn").innerHTML = '<i data-lucide="save" class="h-4 w-4"></i> حفظ النوع';
                document.getElementById("type-name").value = "";
                buildTypeLinkSelect("");
                E.clearFieldErrors(document.getElementById("type-form"));
            }

            document.getElementById("add-btn").addEventListener("click", () => {
                editing = null;
                document.getElementById("link-modal-title").textContent = "إضافة ارتباط مالي";
                document.getElementById("link-save-btn").innerHTML = '<i data-lucide="save" class="h-4 w-4"></i> حفظ الارتباط المالي';
                document.getElementById("link-form").reset();
                E.clearFieldErrors(document.getElementById("link-form"));
                E.openModal("link-modal");
            });

            document.getElementById("add-type-btn").addEventListener("click", () => {
                resetTypeForm();
                E.openModal("type-modal");
            });

            document.addEventListener("click", (e) => {
                const btn = e.target.closest('#links-table [data-edit]');
                if (btn) {
                    const id = btn.getAttribute("data-edit");
                    const payload = currentTable && currentTable._lastPayload;
                    const row = payload && payload.rows.find(r => String(r.id) === String(id));
                    if (!row) return;
                    editing = id;
                    document.getElementById("link-modal-title").textContent = "تعديل الارتباط المالي";
                    document.getElementById("link-save-btn").innerHTML = '<i data-lucide="save" class="h-4 w-4"></i> حفظ التعديلات';
                    document.getElementById("link-name").value = row.name;
                    E.clearFieldErrors(document.getElementById("link-form"));
                    E.openModal("link-modal");
                    return;
                }

                const typeEdit = e.target.closest('#links-table [data-edit-type]');
                if (typeEdit) {
                    /* الأنواع تظهر ضمن كل ارتباط مالي في نفس الجدول */
                }
            });

            document.getElementById("link-form").addEventListener("submit", async (e) => {
                e.preventDefault();
                const form = e.currentTarget;
                E.clearFieldErrors(form);
                const btn = document.getElementById("link-save-btn");
                btn.disabled = true;
                const original = btn.innerHTML;
                btn.innerHTML = '<span class="spinner"></span> جارٍ الحفظ...';
                const data = { name: document.getElementById("link-name").value.trim() };

                try {
                    const url = editing
                        ? "{{ route('financial-links.update', ':id') }}".replace(':id', editing)
                        : D.storeUrl;
                    const res = await E.request(url, { method: editing ? "PUT" : "POST", body: data });
                    if (res.ok === false) {
                        if (res.errors && res.errors.name) E.showFieldError("link-name", res.errors.name[0]);
                        if (res.message) E.toast(res.message, "error");
                        return;
                    }
                    E.toast(res.data.message || "تم الحفظ بنجاح", "success");
                    E.closeModal("link-modal");
                    document.getElementById("link-form").reset();
                    renderTable();
                } catch (err) {
                    if (!/Session expired/.test(err.message)) E.toast(err.message || "تعذر الحفظ", "error");
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = original;
                }
            });

            document.getElementById("type-form").addEventListener("submit", async (e) => {
                e.preventDefault();
                const form = e.currentTarget;
                E.clearFieldErrors(form);
                const btn = document.getElementById("type-save-btn");
                btn.disabled = true;
                const original = btn.innerHTML;
                btn.innerHTML = '<span class="spinner"></span> جارٍ الحفظ...';
                const data = {
                    financial_link_id: document.getElementById("type-link-select").dataset.value || "",
                    name: document.getElementById("type-name").value.trim(),
                };

                try {
                    const url = D.typeStoreUrl;
                    const res = await E.request(url, { method: "POST", body: data });
                    if (res.ok === false) {
                        if (res.errors && res.errors.name) E.showFieldError("type-name", res.errors.name[0]);
                        if (res.errors && res.errors.financial_link_id) E.showFieldError("type-link-select", res.errors.financial_link_id[0]);
                        if (res.message) E.toast(res.message, "error");
                        return;
                    }
                    E.toast(res.data.message || "تم إضافة النوع بنجاح", "success");
                    E.closeModal("type-modal");
                    resetTypeForm();
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