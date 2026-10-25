@extends('layouts.app')

@section('title', 'المستشفيات والأقسام')
@section('description', 'إدارة المستشفيات والأقسام التابعة لها مع إمكانية الإضافة والتعديل والحذف.')

@section('page-title', 'المستشفيات والأقسام')
@section('page-subtitle', 'إدارة بيانات المستشفيات والأقسام المرتبطة بطلبات الإمداد')

@section('content')
    @php
        $hospitalsJson = \App\Models\Hospital::orderBy('name')->get()->map(fn ($h) => ['value' => $h->id, 'label' => $h->name]);
        $hospitalTotal = \App\Models\Hospital::count();
        $departmentTotal = \App\Models\Department::count();
    @endphp

    <!-- بطاقات الإحصائيات -->
    <div class="mb-5 grid gap-3 sm:grid-cols-2">
        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand/10 text-primary">
                <i data-lucide="building-2" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">إجمالي المستشفيات</p>
                <p id="stat-hospitals" class="text-lg font-extrabold text-foreground">{{ number_format($hospitalTotal) }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-success/15 text-success">
                <i data-lucide="layers" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">إجمالي الأقسام</p>
                <p id="stat-departments" class="text-lg font-extrabold text-foreground">{{ number_format($departmentTotal) }}</p>
            </div>
        </div>
    </div>

    <!-- ===== قسم المستشفيات ===== -->
    <div class="mb-8">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="flex items-center gap-2 text-sm font-bold text-foreground">
                <i data-lucide="building-2" class="h-4 w-4 text-primary"></i>
                قائمة المستشفيات
            </h2>
            <button type="button" id="add-hospital-btn"
                class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand px-4 text-[13px] font-bold text-primary-foreground shadow-soft transition-transform hover:-translate-y-0.5">
                <i data-lucide="plus" class="h-4 w-4"></i>
                إضافة مستشفى
            </button>
        </div>
        <div id="hospitals-table"></div>
    </div>

    <!-- ===== قسم الأقسام ===== -->
    <div class="mb-8">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="flex items-center gap-2 text-sm font-bold text-foreground">
                <i data-lucide="layers" class="h-4 w-4 text-primary"></i>
                قائمة الأقسام
            </h2>
            <button type="button" id="add-department-btn"
                class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand px-4 text-[13px] font-bold text-primary-foreground shadow-soft transition-transform hover:-translate-y-0.5">
                <i data-lucide="plus" class="h-4 w-4"></i>
                إضافة قسم
            </button>
        </div>
        <div id="departments-table"></div>
    </div>

    <!-- ===== مودال إضافة/تعديل مستشفى ===== -->
    <div id="hospital-modal" class="fixed inset-0 z-50 items-start justify-center overflow-y-auto bg-foreground/45 p-4 backdrop-blur-sm" style="display:none" onclick="window.emdadat.closeOnBackdrop('hospital-modal', event)">
        <div class="animate-zoom-in my-8 w-full max-w-md rounded-2xl border border-border bg-card shadow-card">
            <div class="flex items-center justify-between gap-4 rounded-t-2xl bg-brand px-5 py-4 text-primary-foreground">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-primary-foreground/15">
                        <i data-lucide="building-2" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h3 id="hospital-modal-title" class="text-base font-bold">إضافة مستشفى</h3>
                        <p class="text-xs opacity-80">قم بإدخال اسم المستشفى الجديد</p>
                    </div>
                </div>
                <button type="button" onclick="window.emdadat.closeModal('hospital-modal')" class="grid h-9 w-9 place-items-center rounded-lg bg-primary-foreground/15 transition-colors hover:bg-primary-foreground/25" aria-label="إغلاق">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form id="hospital-form" class="p-5">
                <div id="hospital-fields" class="space-y-3">
                    <div class="flex items-end gap-2">
                        <div class="flex-1 space-y-1.5">
                            <label class="block text-[13px] font-semibold text-foreground/85">اسم المستشفى <span class="text-destructive">*</span></label>
                            <input name="hospitals[]" type="text" placeholder="مثال: مستشفى الأمل التخصصي"
                                class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                        </div>
                    </div>
                </div>

                <button type="button" id="add-more-hospitals"
                    class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-dashed border-brand/40 bg-brand/5 px-3 py-2 text-[12px] font-semibold text-brand transition-colors hover:bg-brand/10">
                    <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                    إضافة أكتر من مستشفى
                </button>

                <div class="mt-5 flex flex-wrap items-center justify-end gap-2 rounded-b-2xl border-t border-border bg-card pt-4">
                    <button type="button" onclick="window.emdadat.closeModal('hospital-modal')" class="h-10 rounded-lg border border-border bg-card px-5 text-[13px] font-bold text-muted-foreground transition-colors hover:bg-accent">إلغاء</button>
                    <button type="submit" id="hospital-save-btn" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand px-5 text-[13px] font-bold text-primary-foreground shadow-soft transition-transform hover:-translate-y-0.5">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        حفظ المستشفى
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== مودال إضافة/تعديل قسم ===== -->
    <div id="department-modal" class="fixed inset-0 z-50 items-start justify-center overflow-y-auto bg-foreground/45 p-4 backdrop-blur-sm" style="display:none" onclick="window.emdadat.closeOnBackdrop('department-modal', event)">
        <div class="animate-zoom-in my-8 w-full max-w-md rounded-2xl border border-border bg-card shadow-card">
            <div class="flex items-center justify-between gap-4 rounded-t-2xl bg-brand px-5 py-4 text-primary-foreground">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-primary-foreground/15">
                        <i data-lucide="layers" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h3 id="department-modal-title" class="text-base font-bold">إضافة قسم</h3>
                        <p class="text-xs opacity-80">قم بإدخال بيانات القسم الجديد</p>
                    </div>
                </div>
                <button type="button" onclick="window.emdadat.closeModal('department-modal')" class="grid h-9 w-9 place-items-center rounded-lg bg-primary-foreground/15 transition-colors hover:bg-primary-foreground/25" aria-label="إغلاق">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form id="department-form" class="p-5">
                <div class="space-y-4">
                    <div class="field-wrap space-y-1.5">
                        <label class="block text-[13px] font-semibold text-foreground/85">المستشفى <span class="text-destructive">*</span></label>
                        <div id="ss-department-hospital"></div>
                    </div>
                    <div id="department-fields" class="space-y-3">
                        <div class="flex items-end gap-2">
                            <div class="flex-1 space-y-1.5">
                                <label class="block text-[13px] font-semibold text-foreground/85">اسم القسم <span class="text-destructive">*</span></label>
                                <input name="departments[]" type="text" placeholder="مثال: الصيدلية المركزية"
                                    class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                            </div>
                        </div>
                    </div>
                    <button type="button" id="add-more-departments"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-dashed border-brand/40 bg-brand/5 px-3 py-2 text-[12px] font-semibold text-brand transition-colors hover:bg-brand/10">
                        <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                        إضافة أكتر من قسم
                    </button>
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-end gap-2 rounded-b-2xl border-t border-border bg-card pt-4">
                    <button type="button" onclick="window.emdadat.closeModal('department-modal')" class="h-10 rounded-lg border border-border bg-card px-5 text-[13px] font-bold text-muted-foreground transition-colors hover:bg-accent">إلغاء</button>
                    <button type="submit" id="department-save-btn" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand px-5 text-[13px] font-bold text-primary-foreground shadow-soft transition-transform hover:-translate-y-0.5">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        حفظ القسم
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.emdadat_data = {
            hospitals: {!! $hospitalsJson->toJson() !!},
            hospitalsLoadUrl: "{{ route('hospitals.index') }}",
            hospitalsStoreUrl: "{{ route('hospitals.store') }}",
            departmentsLoadUrl: "{{ route('departments.index') }}",
            departmentsStoreUrl: "{{ route('departments.store') }}",
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
            let hospitalTable = null;
            let departmentTable = null;
            let editingHospital = null;
            let editingDepartment = null;
            let departmentHospitalSelect = null;

            function updateStats() {
                const total = document.getElementById("stat-hospitals");
                const dept = document.getElementById("stat-departments");
                if (!total || !dept) return;
                const hp = hospitalTable && hospitalTable._lastPayload;
                const dp = departmentTable && departmentTable._lastPayload;
                if (hp) total.textContent = Number(hp.grandTotal).toLocaleString('en-US');
                if (dp) dept.textContent = Number(dp.grandTotal).toLocaleString('en-US');
            }

            const hospitalColumns = [
                { key: "idx", header: "مسلسل", width: "70px", render: (r, i, p) => (p.from || 1) + i },
                { key: "name", header: "اسم المستشفى" },
                { key: "departments", header: "عدد الأقسام", width: "120px", render: (r) => `<span class="rounded-md bg-accent px-2 py-1 text-[12px] font-semibold text-accent-foreground">${r.departments}</span>` },
                { key: "actions", header: "الإجراءات", width: "140px", render: (r) => r.actions },
            ];

            const departmentColumns = [
                { key: "idx", header: "مسلسل", width: "70px", render: (r, i, p) => (p.from || 1) + i },
                { key: "name", header: "اسم القسم" },
                { key: "hospital", header: "المستشفى" },
                { key: "actions", header: "الإجراءات", width: "140px", render: (r) => r.actions },
            ];

            function renderHospitalTable() {
                hospitalTable = E.renderServerTable(document.getElementById("hospitals-table"), hospitalColumns, {
                    loadUrl: D.hospitalsLoadUrl,
                    exportName: "المستشفيات",
                    searchable: true,
                    minWidth: "",
                    onLoaded: (payload) => {
                        hospitalTable._lastPayload = payload;
                        updateStats();
                        window.lucide.createIcons();
                    },
                });
            }

            function renderDepartmentTable() {
                departmentTable = E.renderServerTable(document.getElementById("departments-table"), departmentColumns, {
                    loadUrl: D.departmentsLoadUrl,
                    exportName: "الأقسام",
                    searchable: true,
                    minWidth: "",
                    onLoaded: (payload) => {
                        departmentTable._lastPayload = payload;
                        updateStats();
                        window.lucide.createIcons();
                    },
                });
            }

            /* ---------- Dynamic field helpers ---------- */
            function addFieldRow(containerId, inputName, placeholder) {
                const container = document.getElementById(containerId);
                const row = document.createElement("div");
                row.className = "flex items-end gap-2";
                row.innerHTML = `
                    <div class="flex-1 space-y-1.5">
                        <input name="${inputName}" type="text" placeholder="${placeholder}"
                            class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                    </div>
                    <button type="button" class="remove-field-btn mb-0.5 grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-destructive/30 text-destructive transition-colors hover:bg-destructive/10" title="حذف">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                    </button>
                `;
                container.appendChild(row);
                window.lucide.createIcons({ nodes: [row] });
                row.querySelector("input").focus();
            }

            function resetFieldsToSingle(containerId, inputName, placeholder) {
                const container = document.getElementById(containerId);
                container.innerHTML = `
                    <div class="flex items-end gap-2">
                        <div class="flex-1 space-y-1.5">
                            <input name="${inputName}" type="text" placeholder="${placeholder}"
                                class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                        </div>
                    </div>
                `;
            }

            document.addEventListener("click", (e) => {
                const btn = e.target.closest(".remove-field-btn");
                if (!btn) return;
                const row = btn.closest("[class*='flex items-end']");
                if (row) row.remove();
            });

            /* ---------- المستشفيات ---------- */
            document.getElementById("add-more-hospitals").addEventListener("click", () => {
                addFieldRow("hospital-fields", "hospitals[]", "اسم مستشفى آخر");
            });

            document.getElementById("add-hospital-btn").addEventListener("click", () => {
                resetHospitalForm();
                E.openModal("hospital-modal");
            });

            function resetHospitalForm() {
                editingHospital = null;
                document.getElementById("hospital-modal-title").textContent = "إضافة مستشفى";
                document.getElementById("hospital-save-btn").innerHTML = '<i data-lucide="save" class="h-4 w-4"></i> حفظ المستشفى';
                document.getElementById("hospital-form").reset();
                E.clearFieldErrors(document.getElementById("hospital-form"));
                resetFieldsToSingle("hospital-fields", "hospitals[]", "مثال: مستشفى الأمل التخصصي");
            }

            document.getElementById("hospital-form").addEventListener("submit", async (e) => {
                e.preventDefault();
                const form = e.currentTarget;
                E.clearFieldErrors(form);
                const btn = document.getElementById("hospital-save-btn");
                btn.disabled = true;
                const original = btn.innerHTML;
                btn.innerHTML = '<span class="spinner"></span> جارٍ الحفظ...';

                const inputs = form.querySelectorAll('input[name="hospitals[]"]');
                const names = Array.from(inputs).map(i => i.value.trim()).filter(v => v.length > 0);

                if (editingHospital) {
                    var data = { name: names[0] || "" };
                } else {
                    var data = names.length === 1 ? { name: names[0] } : { names: names };
                }

                try {
                    const url = editingHospital
                        ? "{{ route('hospitals.update', ':id') }}".replace(':id', editingHospital)
                        : D.hospitalsStoreUrl;
                    const res = await E.request(url, { method: editingHospital ? "PUT" : "POST", body: data });
                    if (res.ok === false) {
                        if (res.errors) {
                            if (res.errors.name) E.showFieldError("hospital-fields", res.errors.name[0]);
                            if (res.errors.names) {
                                const arr = Array.isArray(res.errors.names) ? res.errors.names : Object.values(res.errors.names).flat();
                                E.showFieldError("hospital-fields", arr.join("\n"));
                            }
                        }
                        if (res.message) E.toast(res.message, "error");
                        return;
                    }
                    E.toast(res.data.message || "تم الحفظ بنجاح", "success");
                    E.closeModal("hospital-modal");
                    resetHospitalForm();
                    renderHospitalTable();
                } catch (err) {
                    if (!/Session expired/.test(err.message)) E.toast(err.message || "تعذر الحفظ", "error");
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = original;
                }
            });

            document.addEventListener("click", (e) => {
                const btn = e.target.closest('#hospitals-table [data-edit]');
                if (!btn) return;
                const id = btn.getAttribute("data-edit");
                const payload = hospitalTable && hospitalTable._lastPayload;
                const row = payload && payload.rows.find(r => String(r.id) === String(id));
                if (!row) return;
                editingHospital = id;
                document.getElementById("hospital-modal-title").textContent = "تعديل المستشفى";
                document.getElementById("hospital-save-btn").innerHTML = '<i data-lucide="save" class="h-4 w-4"></i> حفظ التعديلات';
                resetFieldsToSingle("hospital-fields", "hospitals[]", "مثال: مستشفى الأمل التخصصي");
                document.querySelector('#hospital-fields input[name="hospitals[]"]').value = row.name;
                E.clearFieldErrors(document.getElementById("hospital-form"));
                E.openModal("hospital-modal");
            });

            /* ---------- الأقسام ---------- */
            document.getElementById("add-more-departments").addEventListener("click", () => {
                addFieldRow("department-fields", "departments[]", "اسم قسم آخر");
            });

            document.getElementById("add-department-btn").addEventListener("click", () => {
                resetDepartmentForm();
                E.openModal("department-modal");
            });

            function resetDepartmentForm() {
                editingDepartment = null;
                document.getElementById("department-modal-title").textContent = "إضافة قسم";
                document.getElementById("department-save-btn").innerHTML = '<i data-lucide="save" class="h-4 w-4"></i> حفظ القسم';
                document.getElementById("department-form").reset();
                E.clearFieldErrors(document.getElementById("department-form"));
                resetFieldsToSingle("department-fields", "departments[]", "مثال: الصيدلية المركزية");
                initDepartmentSelect();
            }

            function initDepartmentSelect(value) {
                departmentHospitalSelect = E.createSearchableSelect(
                    document.getElementById("ss-department-hospital"),
                    D.hospitals,
                    { placeholder: "ابحث واختر المستشفى...", value: value || "" }
                );
            }

            document.getElementById("department-form").addEventListener("submit", async (e) => {
                e.preventDefault();
                const form = e.currentTarget;
                E.clearFieldErrors(form);
                const btn = document.getElementById("department-save-btn");
                btn.disabled = true;
                const original = btn.innerHTML;
                btn.innerHTML = '<span class="spinner"></span> جارٍ الحفظ...';

                const hospitalId = document.getElementById("ss-department-hospital").dataset.value || "";
                const inputs = form.querySelectorAll('input[name="departments[]"]');
                const names = Array.from(inputs).map(i => i.value.trim()).filter(v => v.length > 0);

                if (editingDepartment) {
                    var data = { hospital_id: hospitalId, name: names[0] || "" };
                } else {
                    var data = names.length === 1
                        ? { hospital_id: hospitalId, name: names[0] }
                        : { hospital_id: hospitalId, names: names };
                }

                try {
                    const url = editingDepartment
                        ? "{{ route('departments.update', ':id') }}".replace(':id', editingDepartment)
                        : D.departmentsStoreUrl;
                    const res = await E.request(url, { method: editingDepartment ? "PUT" : "POST", body: data });
                    if (res.ok === false) {
                        if (res.errors) {
                            if (res.errors.hospital_id) E.showFieldError("ss-department-hospital", res.errors.hospital_id[0]);
                            if (res.errors.name) E.showFieldError("department-fields", res.errors.name[0]);
                            if (res.errors.names) {
                                const arr = Array.isArray(res.errors.names) ? res.errors.names : Object.values(res.errors.names).flat();
                                E.showFieldError("department-fields", arr.join("\n"));
                            }
                        }
                        if (res.message) E.toast(res.message, "error");
                        return;
                    }
                    E.toast(res.data.message || "تم الحفظ بنجاح", "success");
                    E.closeModal("department-modal");
                    resetDepartmentForm();
                    renderDepartmentTable();
                } catch (err) {
                    if (!/Session expired/.test(err.message)) E.toast(err.message || "تعذر الحفظ", "error");
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = original;
                }
            });

            document.addEventListener("click", (e) => {
                const btn = e.target.closest('#departments-table [data-edit]');
                if (!btn) return;
                const id = btn.getAttribute("data-edit");
                const payload = departmentTable && departmentTable._lastPayload;
                const row = payload && payload.rows.find(r => String(r.id) === String(id));
                if (!row) return;
                editingDepartment = id;
                document.getElementById("department-modal-title").textContent = "تعديل القسم";
                document.getElementById("department-save-btn").innerHTML = '<i data-lucide="save" class="h-4 w-4"></i> حفظ التعديلات';
                resetFieldsToSingle("department-fields", "departments[]", "مثال: الصيدلية المركزية");
                document.querySelector('#department-fields input[name="departments[]"]').value = row.name;
                initDepartmentSelect(row.hospitalId);
                E.clearFieldErrors(document.getElementById("department-form"));
                E.openModal("department-modal");
            });

            /* ---------- الحذف ---------- */
            function refreshActive() {
                if (hospitalTable) hospitalTable.refresh();
                if (departmentTable) departmentTable.refresh();
            }

            E.initDeleteHandlers(() => ({
                refresh: refreshActive,
            }));

            renderHospitalTable();
            renderDepartmentTable();
            };
            document.addEventListener('DOMContentLoaded', boot);
        })();
    </script>
@endpush