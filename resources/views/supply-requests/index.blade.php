@extends('layouts.app')

@section('title', 'طلبات الإمداد')
@section('description', 'جدول متابعة طلبات إمداد الأدوية والمستلزمات والكيماويات مع إضافة طلب جديد.')

@section('page-title', 'طلبات الإمداد')
@section('page-subtitle', 'متابعة وتسجيل طلبات الإمداد الواردة من المستشفيات والأقسام')

@section('content')
    <style>
        /* تمييز لوني: الطلبات المُسندة إليك من مستخدم جزئي آخر (مثل إسلام) تظهر بخلفية ذهبية فاتحة */
        #requests-table tr.assigned-to-me td { background-color: #fef3c7 !important; }
        #requests-table tr.assigned-to-me:hover td { background-color: #fde68a !important; }
    </style>
    @php
        $hospitalsJson = $hospitals->map(fn($h) => ['value' => $h->id, 'label' => $h->name]);
        $departmentsSource = $hospitals->mapWithKeys(fn($h) => [
            (string) $h->id => $h->departments->map(fn($d) => ['value' => $d->id, 'label' => $d->name]),
        ]);
        $usersJson = $users->map(fn($u) => ['value' => $u->id, 'label' => $u->name]);
        $total = \App\Models\SupplyRequest::count();
        $totalAmount = \App\Models\SupplyRequest::sum('amount');
        $budgetCount = \App\Models\SupplyRequest::where('financial_link', 'ميزانية')->count();
        $budgetAmount = \App\Models\SupplyRequest::where('financial_link', 'ميزانية')->sum('amount');
        $specialCount = \App\Models\SupplyRequest::where('financial_link', 'حسابات خاصة')->count();
        $specialAmount = \App\Models\SupplyRequest::where('financial_link', 'حسابات خاصة')->sum('amount');
    @endphp

    <!-- بطاقات الإحصائيات -->
    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-2">
        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand/10 text-primary">
                <i data-lucide="package-search" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">إجمالي الطلبات</p>
                <p id="stat-total" class="text-lg font-extrabold text-foreground">
                    {{ number_format($budgetCount + $specialCount) }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-gold/15 text-gold-foreground">
                <i data-lucide="wallet" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">إجمالي القيم المالية</p>
                <p id="stat-total-amount" class="text-lg font-extrabold text-foreground">
                    {{ number_format($totalAmount, 2) }} ج.م
                </p>
            </div>
        </div>
    </div>

    <!-- صف إحصائيات الميزانية -->
    <div class="mb-5 grid gap-3 sm:grid-cols-2">
        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-success/15 text-success">
                <i data-lucide="clipboard-list" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">إجمالي طلبات الميزانية</p>
                <p id="stat-budget-count" class="text-lg font-extrabold text-success">{{ number_format($budgetCount) }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-success/15 text-success">
                <i data-lucide="circle-dollar-sign" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">قيمة إجمالي طلبات الميزانية</p>
                <p id="stat-budget-amount" class="text-lg font-extrabold text-success">{{ number_format($budgetAmount, 2) }}
                    ج.م</p>
            </div>
        </div>
    </div>

    <!-- صف إحصائيات الحسابات الخاصة -->
    <div class="mb-5 grid gap-3 sm:grid-cols-2">
        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-gold/15 text-gold-foreground">
                <i data-lucide="piggy-bank" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">إجمالي طلبات الحسابات الخاصة</p>
                <p id="stat-special-count" class="text-lg font-extrabold text-gold-foreground">
                    {{ number_format($specialCount) }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-gold/15 text-gold-foreground">
                <i data-lucide="circle-dollar-sign" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">قيمة إجمالي طلبات الحسابات الخاصة</p>
                <p id="stat-special-amount" class="text-lg font-extrabold text-gold-foreground">
                    {{ number_format($specialAmount, 2) }} ج.م
                </p>
            </div>
        </div>
    </div>

    <!-- رأس الجدول وزر الإضافة -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-sm font-bold text-foreground">سجل طلبات الإمداد</h2>
        <button type="button" id="add-btn"
            class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand px-4 text-[13px] font-bold text-primary-foreground shadow-soft transition-transform hover:-translate-y-0.5">
            <i data-lucide="plus" class="h-4 w-4"></i>
            إضافة طلب إمداد
        </button>
    </div>

    <!-- الجدول -->
    <div id="requests-table"></div>

    <!-- ===== مودال إضافة إذن تسليم مسعّر (يفتح من زر الصف) ===== -->
    @include('priced-delivery-permits._add-modal', ['idPrefix' => 'permit-'])

    <!-- ===== مودال إضافة طلب إمداد ===== -->
    <div id="add-modal"
        class="fixed inset-0 z-50 items-start justify-center overflow-y-auto bg-foreground/45 p-4 backdrop-blur-sm"
        style="display:none" onclick="window.emdadat.closeOnBackdrop('add-modal', event)">
        <div
            class="animate-zoom-in my-8 w-full max-w-3xl rounded-2xl border border-border bg-card shadow-card">
            <div class="flex items-center justify-between gap-4 rounded-t-2xl bg-brand px-5 py-4 text-primary-foreground">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-primary-foreground/15">
                        <i data-lucide="package-plus" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h3 id="modal-title" class="text-base font-bold">إضافة طلب إمداد</h3>
                        <p id="modal-subtitle" class="text-xs opacity-80">قم بإدخال بيانات طلب الإمداد الجديد</p>
                    </div>
                </div>
                <button type="button" onclick="window.emdadat.closeModal('add-modal')"
                    class="grid h-9 w-9 place-items-center rounded-lg bg-primary-foreground/15 transition-colors hover:bg-primary-foreground/25"
                    aria-label="إغلاق">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form id="add-form" class="p-5">
                @csrf
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="field-wrap space-y-1.5 md:col-span-3">
                        <label class="block text-[13px] font-semibold text-foreground/85">المستخدم المالك للطلب (صاحب الطلب) <span
                                class="text-destructive">*</span></label>
                        <div id="ss-user"></div>
                        <p class="text-[11px] text-muted-foreground">اختر المستخدم الذي سيتبع له هذا الطلب ويُكمِل باقي البيانات.</p>
                    </div>

                    <div class="field-wrap space-y-1.5">
                        <label class="block text-[13px] font-semibold text-foreground/85">رقم طلب الإمداد <span
                                class="text-destructive">*</span></label>
                        <input id="f-request-no" name="supply_request_number" type="text" placeholder="مثال: SR-2026-1024"
                            class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                    </div>

                    <div class="field-wrap space-y-1.5">
                        <label class="block text-[13px] font-semibold text-foreground/85">مسلسل الطلب</label>
                        <input id="f-serial" name="serial" type="text" placeholder="إدخال اختياري"
                            class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                    </div>

                    <div class="field-wrap space-y-1.5">
                        <label class="block text-[13px] font-semibold text-foreground/85">المستشفى <span
                                class="text-destructive">*</span></label>
                        <div id="ss-hospital"></div>
                    </div>

                    <div class="field-wrap space-y-1.5">
                        <label class="block text-[13px] font-semibold text-foreground/85">القسم <span
                                class="text-destructive">*</span></label>
                        <div id="ss-department"></div>
                    </div>

                    <div class="field-wrap space-y-1.5">
                        <label class="block text-[13px] font-semibold text-foreground/85">الارتباط المالي <span
                                class="text-destructive">*</span></label>
                        <div id="ss-financial-link"></div>
                    </div>

                    <div class="field-wrap space-y-1.5">
                        <label class="block text-[13px] font-semibold text-foreground/85">نوع الارتباط المالي <span
                                class="text-destructive">*</span> <span class="text-[11px] text-muted-foreground">(اختر
                                الارتباط المالي أولاً)</span></label>
                        <div id="ss-financial-category"></div>
                    </div>

                    <div class="field-wrap space-y-1.5">
                        <label class="block text-[13px] font-semibold text-foreground/85">قيمة طلب الإمداد <span
                                class="text-destructive">*</span></label>
                        <input id="f-amount" name="amount" type="number" min="0" step="0.01" placeholder="مثال: 45000"
                            class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                    </div>

                    <div class="field-wrap space-y-1.5">
                        <label class="block text-[13px] font-semibold text-foreground/85">تاريخ ورود الطلب <span
                                class="text-destructive">*</span></label>
                        <input id="f-date" name="request_date" type="date"
                            class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow focus:border-primary focus:ring-2 focus:ring-ring/35" />
                    </div>

                    <div class="field-wrap space-y-1.5">
                        <label class="block text-[13px] font-semibold text-foreground/85">العام المالي</label>
                        <div class="relative">
                            <select id="f-fiscal-year" name="fiscal_year"
                                class="h-10 w-full appearance-none rounded-lg border border-input bg-card ps-3 pe-9 text-[13px] text-foreground outline-none transition-shadow focus:border-primary focus:ring-2 focus:ring-ring/35">
                                <option value="">اختر العام المالي...</option>
                                @foreach ($fiscalYears as $fy)
                                    <option value="{{ $fy }}">{{ $fy }}</option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down"
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-end gap-2 rounded-b-2xl border-t border-border bg-card pt-4">
                    <button type="button" onclick="window.emdadat.closeModal('add-modal')"
                        class="h-10 rounded-lg border border-border bg-card px-5 text-[13px] font-bold text-muted-foreground transition-colors hover:bg-accent">إلغاء</button>
                    <button type="submit" id="save-btn"
                        class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand px-5 text-[13px] font-bold text-primary-foreground shadow-soft transition-transform hover:-translate-y-0.5">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        حفظ الطلب
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.emdadat_data = {
            hospitals: {!! $hospitalsJson->toJson() !!},
            users: {!! $usersJson->toJson() !!},
            currentUserId: {{ auth()->id() ?? 'null' }},
            departmentsSource: {!! $departmentsSource->toJson() !!},
            financialLinks: {!! $financialLinks->map(fn ($link) => ['value' => $link->id, 'label' => $link->name, 'types' => $link->types->pluck('name')->all()])->toJson() !!},
            loadUrl: "{{ route('supply-requests.index') }}",
            storeUrl: "{{ route('supply-requests.store') }}",
            updateUrl: "{{ route('supply-requests.update', ':id') }}",
            hospitalsQuickAddUrl: "{{ route('hospitals.quick-add') }}",
            departmentsQuickAddUrl: "{{ route('departments.quick-add') }}",
            userHospitalId: {{ $userHospitalId ?? 'null' }},
            userDepartmentId: {{ $userDepartmentId ?? 'null' }},
            permittedFields: {!! json_encode($permittedFields) !!},
            /* بيانات إذن التسليم المسعّر (المودال المدمج) */
            companies: {!! $companies->map(fn ($c) => ['value' => $c->id, 'label' => $c->name])->toJson() !!},
            permitStoreUrl: "{{ route('priced-delivery-permits.store') }}",
            lookupUrl: "{{ route('supply-requests.lookup') }}",
            companiesQuickAddUrl: "{{ route('companies.quick-add') }}",
            permitPermittedFields: {!! json_encode(auth()->user()->permittedInputFields('priced_delivery_permits')) !!},
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

                function updateStats() {
                    const el = {
                        total: document.getElementById("stat-total"),
                        amount: document.getElementById("stat-total-amount"),
                        budgetCount: document.getElementById("stat-budget-count"),
                        budgetAmount: document.getElementById("stat-budget-amount"),
                        specialCount: document.getElementById("stat-special-count"),
                        specialAmount: document.getElementById("stat-special-amount"),
                    };
                    if (!el.total || !el.amount) return;

                    const payload = currentTable && currentTable._lastPayload ? currentTable._lastPayload : null;
                    if (payload) {
                        const totalCount = Number(payload.budgetCount || 0) + Number(payload.specialCount || 0);
                        el.total.textContent = totalCount.toLocaleString('en-US');
                        el.amount.textContent = E.formatMoney(payload.grandTotal);
                        if (el.budgetCount) el.budgetCount.textContent = Number(payload.budgetCount).toLocaleString('en-US');
                        if (el.budgetAmount) el.budgetAmount.textContent = E.formatMoney(payload.budgetAmount);
                        if (el.specialCount) el.specialCount.textContent = Number(payload.specialCount).toLocaleString('en-US');
                        if (el.specialAmount) el.specialAmount.textContent = E.formatMoney(payload.specialAmount);
                    }
                }

                const columns = [
                    { key: "idx", header: "مسلسل", width: "70px", render: (r, i, p) => (p.from || 1) + i },
                    {
                        key: "type", header: "نوع الطلب", render: (r) => r.type
                            ? `<span class="rounded-md bg-accent px-2 py-1 text-[12px] font-semibold text-accent-foreground">${r.type}</span>`
                            : `<span class="rounded-md bg-muted px-2 py-1 text-[12px] font-semibold text-muted-foreground">—</span>`
                    },
                    { key: "requestNo", header: "رقم طلب الإمداد" },
                    { key: "serial", header: "مسلسل الطلب", render: (r) => r.serial ? r.serial : '<span class="text-muted-foreground/50">—</span>' },
                    { key: "hospital", header: "المستشفى" },
                    { key: "department", header: "القسم" },
                    { key: "financialLink", header: "الارتباط المالي" },
                    { key: "financialCategory", header: "نوع الارتباط المالي" },
                    { key: "amount", header: "قيمة طلب الإمداد", render: (r) => `<span class="font-semibold text-primary">${E.formatMoney(r.amount)}</span>` },
                    { key: "permitsCount", header: "عدد أذونات التسعير", render: (r) => `<span class="font-semibold text-success">${Number(r.permitsCount || 0).toLocaleString('en-US')}</span>` },
                    { key: "deliveredAmount", header: "المبلغ المورد", render: (r) => `<span class="font-semibold text-success">${E.formatMoney(r.deliveredAmount)}</span>` },
                    {
                        key: "remaining", header: "المتبقى", render: (r) => {
                            const v = Math.round((Number(r.remaining) || 0) * 100) / 100;
                            const color = v > 0 ? "text-success" : "text-destructive";
                            const label = v < 0 ? " (مصروف أكثر)" : "";
                            return `<span class="font-semibold ${color}">${E.formatMoney(v)}${label}</span>`;
                        }
                    },
                    { key: "date", header: "تاريخ ورود الطلب" },
                    { key: "fiscalYear", header: "العام المالي" },
                    { key: "assignedToName", header: "صاحب الطلب", render: (r) => `<span class="text-muted-foreground">${r.assignedToName}</span>` },
                    { key: "actions", header: "الإجراءات", width: "140px", render: (r) => r.actions },
                ];

                function renderTable() {
                    currentTable = E.renderServerTable(document.getElementById("requests-table"), columns, {
                        loadUrl: D.loadUrl,
                        exportName: "طلبات التوريد",
                        searchable: true,
                        rowClass: (row) =>
                            String(row.assignedToId || "") === String(D.currentUserId || "") &&
                            String(row.createdById || "") !== String(D.currentUserId || "") &&
                            !row.ownerProcessedAt
                                ? "assigned-to-me"
                                : "",
                        onLoaded: (payload) => {
                            currentTable._lastPayload = payload;
                            updateStats();
                            window.lucide.createIcons();
                        },
                    });
                }

                let hospitalSelectRef = null;
                let linkSelectRef = null;
                let categorySelectRef = null;
                let userSelectRef = null;

                function initUserSelect(value) {
                    userSelectRef = E.createSearchableSelect(document.getElementById("ss-user"), D.users, {
                        placeholder: "ابحث واختر المستخدم...",
                        value: value ? String(value) : "",
                    });
                }

                const fieldMap = {
                    supply_request_number: "f-request-no",
                    serial: "f-serial",
                    hospital_id: "ss-hospital",
                    department_id: "ss-department",
                    financial_link: "ss-financial-link",
                    financial_category: "ss-financial-category",
                    amount: "f-amount",
                    request_date: "f-date",
                    fiscal_year: "f-fiscal-year",
                };

                function applyUserOwnerVisibility() {
                    /* المستخدم الكامل (من غير input_permissions) ميخفيش اختيار صاحب الطلب — الطلب يخصه هو */
                    const wrap = document.getElementById("ss-user")?.closest(".field-wrap");
                    if (wrap) {
                        wrap.style.display = D.permittedFields === null ? "none" : "";
                    }
                }

                function applyPermissions() {
                    applyUserOwnerVisibility();
                    const pf = D.permittedFields;
                    if (!pf) return;
                    const allowed = new Set(pf);
                    Object.entries(fieldMap).forEach(([field, id]) => {
                        const el = document.getElementById(id);
                        if (!el) return;
                        const wrap = el.closest(".field-wrap");
                        if (!wrap) return;
                        if (allowed.has(field)) {
                            wrap.style.display = "";
                        } else {
                            wrap.style.display = "none";
                        }
                    });
                }

                function initSelects() {
                    const hid = D.userHospitalId ? String(D.userHospitalId) : "";
                    hospitalSelectRef = E.createSearchableSelect(document.getElementById("ss-hospital"), D.hospitals, {
                        placeholder: "اختر المستشفى...",
                        onSelect: handleHospitalSelect,
                        value: hid,
                    });
                    if (hid) {
                        const depts = D.departmentsSource[hid] || [];
                        departmentHospitalSelect = E.createSearchableSelect(document.getElementById("ss-department"), depts, {
                            placeholder: "اختر القسم...",
                            value: D.userDepartmentId ? String(D.userDepartmentId) : "",
                        });
                    } else {
                        departmentHospitalSelect = E.createSearchableSelect(document.getElementById("ss-department"), [], { placeholder: "اختر المستشفى أولاً..." });
                    }
                }

                function buildFinancialLinkOptions(selectedValue) {
                    linkSelectRef = E.createSearchableSelect(document.getElementById("ss-financial-link"), D.financialLinks, {
                        placeholder: "ابحث واختر الارتباط المالي...",
                        value: selectedValue ? String(selectedValue) : "",
                        onSelect: (val) => {
                            if (!val) {
                                categorySelectRef = E.createSearchableSelect(document.getElementById("ss-financial-category"), [], { placeholder: "اختر الارتباط المالي أولاً..." });
                                return;
                            }
                            const link = D.financialLinks.find((l) => String(l.value) === String(val));
                            buildFinancialCategoryOptions(link ? link.label : "", "");
                        },
                    });
                }

                function buildFinancialCategoryOptions(link, selectedValue) {
                    const found = D.financialLinks.find((l) => l.label === link);
                    const list = found ? (found.types || []) : [];
                    categorySelectRef = E.createSearchableSelect(document.getElementById("ss-financial-category"), list, {
                        placeholder: list.length ? "اختر نوع الارتباط المالي..." : "اختر الارتباط المالي أولاً...",
                        value: selectedValue ? String(selectedValue) : "",
                    });
                }

                function handleHospitalSelect() {
                    const hospitalId = document.getElementById("ss-hospital").dataset.value;
                    const depts = D.departmentsSource[hospitalId] || [];
                    departmentHospitalSelect = E.createSearchableSelect(document.getElementById("ss-department"), depts, {
                        placeholder: hospitalId ? "اختر القسم..." : "اختر المستشفى أولاً...",
                    });
                }

                function resetAddForm() {
                    document.getElementById("add-form").reset();
                    document.getElementById("ss-hospital").innerHTML = "";
                    document.getElementById("ss-department").innerHTML = "";
                    document.getElementById("ss-financial-link").innerHTML = "";
                    document.getElementById("ss-financial-category").innerHTML = "";
                    document.getElementById("ss-user").innerHTML = "";
                    initUserSelect(D.currentUserId || "");
                    initSelects();
                    buildFinancialLinkOptions("");
                    applyPermissions();
                }

                let editing = null;

                function getRow(id) {
                    const payload = currentTable && currentTable._lastPayload;
                    return payload ? payload.rows.find((r) => String(r.id) === String(id)) : null;
                }

                function setModalMode(isEdit) {
                    document.getElementById("modal-title").textContent = isEdit ? "تعديل طلب إمداد" : "إضافة طلب إمداد";
                    document.getElementById("modal-subtitle").textContent = isEdit ? "قم بتحديث بيانات طلب الإمداد" : "قم بإدخال بيانات طلب الإمداد الجديد";
                    document.getElementById("save-btn").innerHTML = isEdit
                        ? '<i data-lucide="save" class="h-4 w-4"></i> حفظ التعديلات'
                        : '<i data-lucide="save" class="h-4 w-4"></i> حفظ الطلب';
                    window.lucide.createIcons();
                }

                function showViewModal(row) {
                    const rows = [
                        ["نوع الطلب", row.type || "—"],
                        ["رقم طلب الإمداد", row.requestNo],
                        ["مسلسل الطلب", row.serial || "—"],
                        ["المستشفى", row.hospital],
                        ["القسم", row.department],
                        ["الارتباط المالي", row.financialLink],
                        ["نوع الارتباط المالي", row.financialCategory],
                        ["قيمة طلب الإمداد", E.formatMoney(row.amount)],
                        ["عدد أذونات التسعير", row.permitsCount ?? 0],
                        ["المبلغ المورد", E.formatMoney(row.deliveredAmount)],
                        ["المتبقى من قيمة طلب الإمداد", E.formatMoney(row.remaining)],
                        ["تاريخ ورود الطلب", row.date],
                        ["العام المالي", row.fiscalYear],
                        ["صاحب الطلب", row.assignedToName],
                    ];
                    const html = rows.map(([k, v]) =>
                        `<div style="display:flex;justify-content:space-between;gap:12px;padding:7px 10px;border-radius:8px;background:#f1f5f9;text-align:right"><span style="color:#64748b;font-size:13px;font-weight:600">${k}</span><span style="color:#0f172a;font-size:13px;font-weight:700">${v ?? "—"}</span></div>`
                    ).join("");
                    Swal.fire({
                        title: "بيانات طلب الإمداد",
                        html: `<div dir="rtl" style="display:grid;gap:6px">${html}</div>`,
                        width: 480,
                        confirmButtonText: "إغلاق",
                        confirmButtonColor: "#0f766e",
                    });
                }

                document.addEventListener("click", (e) => {
                    const viewBtn = e.target.closest("#requests-table [data-view]");
                    if (viewBtn) {
                        const row = getRow(viewBtn.getAttribute("data-view"));
                        if (row) showViewModal(row);
                        return;
                    }
                    const editBtn = e.target.closest("#requests-table [data-edit]");
                    if (!editBtn) return;
                    const row = getRow(editBtn.getAttribute("data-edit"));
                    if (!row) return;
                    editing = row.id;
                    resetAddForm();
                    document.getElementById("f-request-no").value = row.requestNo || "";
                    document.getElementById("f-serial").value = row.serial || "";
                    const hospitalId = String(row.hospitalId || "");
                    E.createSearchableSelect(document.getElementById("ss-hospital"), D.hospitals, {
                        placeholder: "اختر المستشفى...",
                        onSelect: handleHospitalSelect,
                        value: hospitalId,
                    });
                    E.createSearchableSelect(document.getElementById("ss-department"), D.departmentsSource[hospitalId] || [], {
                        placeholder: "اختر القسم...",
                        value: String(row.departmentId || ""),
                    });
                    buildFinancialLinkOptions(row.financialLink ? String(D.financialLinks.find((l) => l.label === row.financialLink)?.value ?? "") : "");
                    buildFinancialCategoryOptions(row.financialLink || "", row.financialCategory || "");
                    document.getElementById("f-amount").value = row.amount != null ? Number(row.amount) : "";
                    document.getElementById("f-date").value = row.date || "";
                    document.getElementById("f-fiscal-year").value = row.fiscalYear !== "—" ? (row.fiscalYear || "") : "";
                    initUserSelect(row.assignedToId || "");
                    setModalMode(true);
                    E.openModal("add-modal");
                });

                document.getElementById("add-btn").addEventListener("click", () => {
                    editing = null;
                    resetAddForm();
                    setModalMode(false);
                    E.openModal("add-modal");
                });

                const FIELD_IDS = {
                    supply_request_number: "f-request-no",
                    serial: "f-serial",
                    hospital_id: "ss-hospital",
                    department_id: "ss-department",
                    financial_link: "ss-financial-link",
                    financial_category: "ss-financial-category",
                    amount: "f-amount",
                    request_date: "f-date",
                    fiscal_year: "f-fiscal-year",
                };

                function applyFieldErrors(errors) {
                    Object.keys(errors || {}).forEach((key) => {
                        if (FIELD_IDS[key]) E.showFieldError(FIELD_IDS[key], errors[key][0]);
                    });
                }

                document.getElementById("add-form").addEventListener("submit", async (e) => {
                    e.preventDefault();
                    const form = e.currentTarget;
                    E.clearFieldErrors(form);

                    const linkId = document.getElementById("ss-financial-link").dataset.value || "";
                        const linkName = (D.financialLinks.find((l) => String(l.value) === String(linkId)) || {}).label || "";

                        const data = {
                            assigned_to: document.getElementById("ss-user").dataset.value || "",
                            supply_request_number: document.getElementById("f-request-no").value.trim(),
                            serial: document.getElementById("f-serial").value.trim(),
                            hospital_id: document.getElementById("ss-hospital").dataset.value || "",
                            department_id: document.getElementById("ss-department").dataset.value || "",
                            financial_link: linkName,
                            financial_category: document.getElementById("ss-financial-category").dataset.value || "",
                            amount: document.getElementById("f-amount").value,
                            request_date: document.getElementById("f-date").value,
                            fiscal_year: document.getElementById("f-fiscal-year").value,
                        };

                    const btn = document.getElementById("save-btn");
                    btn.disabled = true;
                    const original = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner"></span> جارٍ الحفظ...';

                    try {
                        const url = editing ? D.updateUrl.replace(":id", editing) : D.storeUrl;
                        const res = await E.request(url, { method: editing ? "PUT" : "POST", body: data });
                        if (res.ok === false) {
                            applyFieldErrors(res.errors);
                            E.toast(res.message || "من فضلك أكمل الحقول المطلوبة.", "error");
                            return;
                        }
                        E.toast(res.data.message || (editing ? "تم تعديل طلب الإمداد بنجاح" : "تمت إضافة طلب الإمداد بنجاح"), "success");
                        E.closeModal("add-modal");
                        resetAddForm();
                        renderTable();
                    } catch (err) {
                        if (!/Session expired/.test(err.message)) E.toast(err.message || "تعذر الحفظ", "error");
                    } finally {
                        btn.disabled = false;
                        btn.innerHTML = original;
                    }
                });

                function initStatsRefresh() {
                    const table = document.getElementById("requests-table");
                    const target = document.querySelector("main");
                    if (target) {
                        target.addEventListener("submit", (e) => {
                            if (e.target.matches("#add-form")) return;
                        });
                    }
                }

                /* ===== مودال إذن التسليم المسعّر (مدمج) ===== */
                const P = (id) => "permit-" + id;
                let permitCompanySelectRef = null;
                let permitUserSelectRef = null;

                function initPermitUserSelect(value) {
                    permitUserSelectRef = E.createSearchableSelect(document.getElementById(P("ss-user")), D.users, {
                        placeholder: "ابحث واختر المستخدم...",
                        value: value ? String(value) : "",
                    });
                }

                function initPermitCompanySelect(value) {
                    permitCompanySelectRef = E.createSearchableSelect(document.getElementById(P("ss-company")), D.companies, {
                        placeholder: "ابحث واختر الشركة...",
                        value: value ? String(value) : "",
                    });
                }

                const permitSrTextFields = [
                    [P("sr-type"), "type"],
                    [P("sr-number"), "requestNo"],
                    [P("sr-hospital"), "hospital"],
                    [P("sr-department"), "department"],
                    [P("sr-link"), "financialLink"],
                    [P("sr-category"), "financialCategory"],
                ];

                function hidePermitSupplyBox() {
                    document.getElementById(P("supply-info-box")).classList.add("hidden");
                }

                /* ملء البوكس مباشرة من بيانات صف الجدول (بدون lookup — الصف بنفسه هو مصدر البيانات) */
                function fillPermitSupplyBox(row) {
                    permitSrTextFields.forEach(([id, key]) => {
                        document.getElementById(id).value = row[key] || "—";
                    });
                    document.getElementById(P("sr-amount")).value = E.formatMoney(row.amount);
                    document.getElementById(P("sr-date")).value = row.date || "—";
                    document.getElementById(P("sr-permits-count")).value = row.permitsCount != null ? Number(row.permitsCount).toLocaleString("en-US") : "—";
                    document.getElementById(P("sr-delivered")).value = E.formatMoney(row.deliveredAmount);
                    const remEl = document.getElementById(P("sr-remaining"));
                    remEl.value = E.formatMoney(row.remaining);
                    const v = Math.round((Number(row.remaining) || 0) * 100) / 100;
                    remEl.classList.toggle("text-success", v > 0);
                    remEl.classList.toggle("text-destructive", v <= 0);
                    document.getElementById(P("supply-info-box")).classList.remove("hidden");
                }

                const permitFieldMap = {
                    delivery_amount: P("f-amount"),
                    supply_request_number: P("f-supply-no"),
                    delivery_permit_number: P("f-note-no"),
                    issue_date: P("f-issue-date"),
                    company_id: P("ss-company"),
                    form_date: P("f-form-date"),
                    status: P("f-status"),
                    fully_delivered: P("f-fully-delivered"),
                };

                function applyPermitPermissions() {
                    const wrap = document.getElementById(P("ss-user"))?.closest(".field-wrap");
                    if (wrap) wrap.style.display = D.permitPermittedFields === null ? "none" : "";
                    const pf = D.permitPermittedFields;
                    if (!pf) return;
                    const allowed = new Set(pf);
                    Object.entries(permitFieldMap).forEach(([field, id]) => {
                        const el = document.getElementById(id);
                        if (!el) return;
                        const elWrap = el.closest(".field-wrap");
                        if (!elWrap) return;
                        elWrap.style.display = allowed.has(field) ? "" : "none";
                    });
                }

                const permitFieldIds = permitFieldMap;

                function applyPermitFieldErrors(errors) {
                    Object.keys(errors || {}).forEach((key) => {
                        if (permitFieldIds[key]) E.showFieldError(permitFieldIds[key], errors[key][0]);
                    });
                }

                function openPermitModalFor(row) {
                    document.getElementById(P("add-form")).reset();
                    hidePermitSupplyBox();
                    document.getElementById(P("f-supply-no")).value = row.requestNo || "";
                    fillPermitSupplyBox(row);
                    initPermitUserSelect(D.currentUserId || "");
                    initPermitCompanySelect("");
                    applyPermitPermissions();
                    window.lucide.createIcons();
                    E.openModal(P("add-modal"));
                }

                document.getElementById(P("add-form")).addEventListener("submit", async (e) => {
                    e.preventDefault();
                    const form = e.currentTarget;
                    E.clearFieldErrors(form);

                    const data = {
                        assigned_to: document.getElementById(P("ss-user")).dataset.value || "",
                        delivery_amount: document.getElementById(P("f-amount")).value,
                        supply_request_number: document.getElementById(P("f-supply-no")).value.trim(),
                        delivery_permit_number: document.getElementById(P("f-note-no")).value.trim(),
                        issue_date: document.getElementById(P("f-issue-date")).value,
                        company_id: document.getElementById(P("ss-company")).dataset.value || "",
                        form_date: document.getElementById(P("f-form-date")).value,
                        status: document.getElementById(P("f-status")).value,
                        fully_delivered: document.getElementById(P("f-fully-delivered")).checked ? 1 : 0,
                    };

                    const btn = document.getElementById(P("save-btn"));
                    btn.disabled = true;
                    const original = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner"></span> جارٍ الحفظ...';

                    try {
                        const res = await E.request(D.permitStoreUrl, { method: "POST", body: data });
                        if (res.ok === false) {
                            applyPermitFieldErrors(res.errors);
                            E.toast(res.message || "من فضلك أكمل الحقول المطلوبة.", "error");
                            return;
                        }
                        E.toast(res.data.message || "تمت إضافة إذن التسليم المسعّر بنجاح", "success");
                        E.closeModal(P("add-modal"));
                        renderTable();
                    } catch (err) {
                        if (!/Session expired/.test(err.message)) E.toast(err.message || "تعذر الحفظ", "error");
                    } finally {
                        btn.disabled = false;
                        btn.innerHTML = original;
                    }
                });

                document.addEventListener("click", (e) => {
                    const permitBtn = e.target.closest("#requests-table [data-add-permit]");
                    if (!permitBtn) return;
                    const row = getRow(permitBtn.getAttribute("data-add-permit"));
                    if (!row) return;
                    openPermitModalFor(row);
                });

                E.attachQuickAdd("#" + P("ss-company"), () => permitCompanySelectRef, {
                    url: D.companiesQuickAddUrl,
                    label: "الشركة",
                    addLabel: "إضافة شركة جديدة",
                    placeholder: "مثال: شركة الدواء الحديثة",
                });

                E.initDeleteHandlers(() => currentTable);
                renderTable();
                initSelects();
                buildFinancialLinkOptions("");

                /* إضافة سريعة: مستشفى / قسم */
                E.attachQuickAdd("#ss-hospital", () => hospitalSelectRef, {
                    url: D.hospitalsQuickAddUrl,
                    label: "المستشفى",
                    addLabel: "إضافة مستشفى جديدة",
                    placeholder: "مثال: مستشفى الأمل",
                });
                E.attachQuickAdd("#ss-department", () => departmentHospitalSelect, {
                    url: D.departmentsQuickAddUrl,
                    label: "القسم",
                    addLabel: "إضافة قسم جديد",
                    customForm: {
                        html: `
                                                    <div dir="rtl" class="space-y-4 text-right">
                                                        <div>
                                                            <label class="mb-1 block text-[13px] font-semibold text-foreground/85">اسم القسم <span class="text-destructive">*</span></label>
                                                            <input id="qa-dept-name" type="text" placeholder="مثال: الصيدلية المركزية"
                                                                class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                                                        </div>
                                                        <div>
                                                            <label class="mb-1 block text-[13px] font-semibold text-foreground/85">المستشفى <span class="text-destructive">*</span></label>
                                                            <div id="qa-dept-hospital"></div>
                                                        </div>
                                                    </div>
                                                `,
                        init: () => {
                            /* اقرأ المستشفيات من قائمة الصفحة الحية عشان تشوف أي مستشفى أضيف حديثًا */
                            const liveHospitals = (hospitalSelectRef && hospitalSelectRef.options()) || D.hospitals;
                            E.createSearchableSelect(document.getElementById('qa-dept-hospital'), liveHospitals, {
                                placeholder: 'ابحث واختر المستشفى...',
                            });
                        },
                        getValue: () => ({
                            name: document.getElementById('qa-dept-name').value.trim(),
                            hospital_id: document.getElementById('qa-dept-hospital').dataset.value || '',
                        }),
                        validate: (vals) => {
                            if (!vals.name) return 'اسم القسم مطلوب.';
                            if (!vals.hospital_id) return 'اختر المستشفى أولاً.';
                            return null;
                        },
                    },
                });
            };
            document.addEventListener('DOMContentLoaded', boot);
        })();
    </script>
@endpush