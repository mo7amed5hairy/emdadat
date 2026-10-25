@extends('layouts.app')

@section('title', 'إدارة المستخدمين')
@section('description', 'إضافة وتعديل وحذف المستخدمين وتفويض الاطلاع على بيانات المستخدمين الآخرين.')

@section('page-title', 'إدارة المستخدمين')
@section('page-subtitle', 'التحكم في حسابات المستخدمين وتفويض صلاحيات الاطلاع على البيانات')

@section('content')
    @php
        $usersJson = $users->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'username' => $u->username,
            'is_admin' => (bool) $u->is_admin,
            'category' => $u->category,
            'hospital_id' => $u->hospital_id,
            'department_id' => $u->department_id,
            'input_permissions' => is_array($u->input_permissions) ? $u->input_permissions : null,
        ]);
        $hospitalsJson = $hospitals->map(fn ($h) => ['value' => $h->id, 'label' => $h->name]);
        $departmentsSource = $hospitals->mapWithKeys(fn ($h) => [
            (string) $h->id => $h->departments->map(fn ($d) => ['value' => $d->id, 'label' => $d->name]),
        ]);
    @endphp

    <!-- رأس الجدول وزر الإضافة -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-sm font-bold text-foreground">سجل المستخدمين</h2>
        <button type="button" id="add-btn"
            class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand px-4 text-[13px] font-bold text-primary-foreground shadow-soft transition-transform hover:-translate-y-0.5">
            <i data-lucide="user-plus" class="h-4 w-4"></i>
            إضافة مستخدم
        </button>
    </div>

    <!-- الجدول -->
    <div id="users-table"></div>

    <!-- ===== مودال إضافة / تعديل مستخدم ===== -->
    <div id="user-modal" class="fixed inset-0 z-50 items-start justify-center overflow-y-auto bg-foreground/45 p-4 backdrop-blur-sm" style="display:none" onclick="window.emdadat.closeOnBackdrop('user-modal', event)">
        <div class="animate-zoom-in my-8 w-full max-w-2xl rounded-2xl border border-border bg-card shadow-card">
            <div class="flex items-center justify-between gap-4 rounded-t-2xl bg-brand px-5 py-4 text-primary-foreground">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-primary-foreground/15">
                        <i data-lucide="user-cog" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h3 id="modal-title" class="text-base font-bold">إضافة مستخدم</h3>
                        <p class="text-xs opacity-80">قم بإدخال بيانات المستخدم وصلاحياته</p>
                    </div>
                </div>
                <button type="button" onclick="window.emdadat.closeModal('user-modal')" class="grid h-9 w-9 place-items-center rounded-lg bg-primary-foreground/15 transition-colors hover:bg-primary-foreground/25" aria-label="إغلاق">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form id="user-form" class="p-5">
                @csrf
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="field-wrap space-y-1.5">
                        <label class="block text-[13px] font-semibold text-foreground/85">الاسم الكامل <span class="text-destructive">*</span></label>
                        <input id="f-name" name="name" type="text" placeholder="مثال: أحمد محمد"
                            class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                    </div>

                    <div class="field-wrap space-y-1.5">
                        <label class="block text-[13px] font-semibold text-foreground/85">اسم المستخدم <span class="text-destructive">*</span></label>
                        <input id="f-username" name="username" type="text" placeholder="مثال: ahmed"
                            class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                    </div>

                    <div class="field-wrap space-y-1.5 md:col-span-2">
                        <label class="block text-[13px] font-semibold text-foreground/85">كلمة المرور <span class="text-destructive">*</span></label>
                        <div class="relative">
                            <input id="f-password" name="password" type="password" placeholder="6 أحرف على الأقل"
                                class="h-10 w-full rounded-lg border border-input bg-card ps-3 pe-10 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                            <button type="button" data-pw-toggle="f-password" class="absolute left-2 top-1/2 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-lg text-muted-foreground transition-colors hover:bg-accent">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </button>
                        </div>
                        <p id="password-hint" class="text-[11px] text-muted-foreground">اتركها فارغة عند التعديل للإبقاء على كلمة المرور الحالية.</p>
                    </div>

                    <div class="field-wrap space-y-1.5 md:col-span-2">
                        <label class="block text-[13px] font-semibold text-foreground/85">فئة المستخدم</label>
                        <div class="relative">
                            <select id="f-category" name="category" class="h-10 w-full appearance-none rounded-lg border border-input bg-card ps-3 pe-9 text-[13px] text-foreground outline-none transition-shadow focus:border-primary focus:ring-2 focus:ring-ring/35">
                                <option value="">بدون فئة</option>
                                <option value="مستلزمات">مستلزمات</option>
                                <option value="كيماويات">كيماويات</option>
                                <option value="أدوية">أدوية</option>
                            </select>
                            <i data-lucide="chevron-down" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                        </div>
                        <p class="text-[11px] text-muted-foreground">عند اختيار فئة، يُثبَّت نوع الطلب تلقائيًا على طلبات الإمداد التي يسجلها هذا المستخدم.</p>
                    </div>

                    <div class="field-wrap space-y-1.5">
                        <label class="block text-[13px] font-semibold text-foreground/85">المستشفى</label>
                        <div id="ss-user-hospital"></div>
                        <p class="text-[11px] text-muted-foreground">اختياري — عند تحديد مستشفى، تُفتح خانة القسم لتحديد قسم تلقائي.</p>
                    </div>

                    <div class="field-wrap space-y-1.5">
                        <label class="block text-[13px] font-semibold text-foreground/85">القسم</label>
                        <div id="ss-user-department"></div>
                        <p class="text-[11px] text-muted-foreground">يظهر لك قسم واحد أو أكثر لمستشفى واحد فقط.</p>
                    </div>

                    <div class="field-wrap space-y-1.5 md:col-span-2">
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-input bg-card px-3 py-2.5">
                            <input id="f-is-admin" name="is_admin" type="checkbox" value="1"
                                class="h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" />
                            <span class="text-[13px] font-semibold text-foreground/85">جعل هذا المستخدم مسئول نظام (يطلع على بيانات جميع المستخدمين)</span>
                        </label>
                        <p class="text-[11px] text-muted-foreground">بالضغط عليها يتمكن المستخدم من الإطلاع على ادخالات باقى المستخدمين.</p>
                    </div>

                    <!-- تحديد إضافة مدخلات -->
                    <div class="field-wrap md:col-span-2 space-y-3 rounded-xl border border-border bg-muted/40 p-4">
                        <p class="text-[13px] font-bold text-foreground">تحديد إضافة مدخلات</p>

                        <!-- طلبات الإمداد -->
                        <div class="space-y-2">
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-input bg-card px-3 py-2">
                                <input id="perm-supply-on" type="checkbox" value="1" class="h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" />
                                <span class="text-[13px] font-semibold text-foreground/85">طلبات الإمداد</span>
                            </label>
                            <div id="perm-supply-fields" class="mr-6 space-y-1.5">
                                <label class="flex cursor-pointer items-center gap-2 px-2 py-1"><input data-perm="supply" value="supply_request_number" type="checkbox" class="perm-supply-cb h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" /><span class="text-[12px] text-foreground/80">رقم طلب الإمداد</span></label>
                                <label class="flex cursor-pointer items-center gap-2 px-2 py-1"><input data-perm="supply" value="serial" type="checkbox" class="perm-supply-cb h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" /><span class="text-[12px] text-foreground/80">مسلسل الطلب</span></label>
                                <label class="flex cursor-pointer items-center gap-2 px-2 py-1"><input data-perm="supply" value="hospital_id" type="checkbox" class="perm-supply-cb h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" /><span class="text-[12px] text-foreground/80">المستشفى</span></label>
                                <label class="flex cursor-pointer items-center gap-2 px-2 py-1"><input data-perm="supply" value="department_id" type="checkbox" class="perm-supply-cb h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" /><span class="text-[12px] text-foreground/80">القسم</span></label>
                                <label class="flex cursor-pointer items-center gap-2 px-2 py-1"><input data-perm="supply" value="financial_link" type="checkbox" class="perm-supply-cb h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" /><span class="text-[12px] text-foreground/80">الارتباط المالي</span></label>
                                <label class="flex cursor-pointer items-center gap-2 px-2 py-1"><input data-perm="supply" value="financial_category" type="checkbox" class="perm-supply-cb h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" /><span class="text-[12px] text-foreground/80">نوع الارتباط المالي</span></label>
                                <label class="flex cursor-pointer items-center gap-2 px-2 py-1"><input data-perm="supply" value="amount" type="checkbox" class="perm-supply-cb h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" /><span class="text-[12px] text-foreground/80">قيمة طلب الإمداد</span></label>
                                <label class="flex cursor-pointer items-center gap-2 px-2 py-1"><input data-perm="supply" value="request_date" type="checkbox" class="perm-supply-cb h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" /><span class="text-[12px] text-foreground/80">تاريخ ورود الطلب</span></label>
                                <label class="flex cursor-pointer items-center gap-2 px-2 py-1"><input data-perm="supply" value="fiscal_year" type="checkbox" class="perm-supply-cb h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" /><span class="text-[12px] text-foreground/80">العام المالي</span></label>
                            </div>
                        </div>

                        <!-- أذونات التسليم المسعّرة -->
                        <div class="space-y-2">
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-input bg-card px-3 py-2">
                                <input id="perm-permits-on" type="checkbox" value="1" class="h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" />
                                <span class="text-[13px] font-semibold text-foreground/85">أذونات التسليم المسعّرة</span>
                            </label>
                            <div id="perm-permits-fields" class="mr-6 space-y-1.5">
                                <label class="flex cursor-pointer items-center gap-2 px-2 py-1"><input data-perm="permits" value="delivery_amount" type="checkbox" class="perm-permits-cb h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" /><span class="text-[12px] text-foreground/80">قيمة الإذن</span></label>
                                <label class="flex cursor-pointer items-center gap-2 px-2 py-1"><input data-perm="permits" value="supply_request_number" type="checkbox" class="perm-permits-cb h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" /><span class="text-[12px] text-foreground/80">رقم الإمداد</span></label>
                                <label class="flex cursor-pointer items-center gap-2 px-2 py-1"><input data-perm="permits" value="delivery_permit_number" type="checkbox" class="perm-permits-cb h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" /><span class="text-[12px] text-foreground/80">رقم الإذن</span></label>
                                <label class="flex cursor-pointer items-center gap-2 px-2 py-1"><input data-perm="permits" value="issue_date" type="checkbox" class="perm-permits-cb h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" /><span class="text-[12px] text-foreground/80">تاريخ الإصدار</span></label>
                                <label class="flex cursor-pointer items-center gap-2 px-2 py-1"><input data-perm="permits" value="company_id" type="checkbox" class="perm-permits-cb h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" /><span class="text-[12px] text-foreground/80">الشركة</span></label>
                                <label class="flex cursor-pointer items-center gap-2 px-2 py-1"><input data-perm="permits" value="form_date" type="checkbox" class="perm-permits-cb h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" /><span class="text-[12px] text-foreground/80">تاريخ تحرير الاستمارة</span></label>
                                <label class="flex cursor-pointer items-center gap-2 px-2 py-1"><input data-perm="permits" value="status" type="checkbox" class="perm-permits-cb h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" /><span class="text-[12px] text-foreground/80">حالة الصرف</span></label>
                                <label class="flex cursor-pointer items-center gap-2 px-2 py-1"><input data-perm="permits" value="fully_delivered" type="checkbox" class="perm-permits-cb h-4 w-4 rounded border-border text-primary focus:ring-2 focus:ring-ring/35" /><span class="text-[12px] text-foreground/80">تم التوريد بالكامل</span></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-end gap-2 rounded-b-2xl border-t border-border bg-card pt-4">
                    <button type="button" onclick="window.emdadat.closeModal('user-modal')" class="h-10 rounded-lg border border-border bg-card px-5 text-[13px] font-bold text-muted-foreground transition-colors hover:bg-accent">إلغاء</button>
                    <button type="submit" id="save-btn" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand px-5 text-[13px] font-bold text-primary-foreground shadow-soft transition-transform hover:-translate-y-0.5">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        حفظ
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.emdadat_data = {
            users: {!! $usersJson->toJson() !!},
            hospitals: {!! $hospitalsJson->toJson() !!},
            departmentsSource: {!! $departmentsSource->toJson() !!},
            loadUrl: "{{ route('users.index') }}",
            storeUrl: "{{ route('users.store') }}",
            updateUrl: "{{ route('users.update', '__ID__') }}",
            currentUserId: {{ auth()->id() }},
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
                let editingId = null;
                let hospitalSelect = null;
                let departmentSelect = null;

                function handleUserHospitalSelect() {
                    const hid = document.getElementById("ss-user-hospital").dataset.value;
                    const depts = D.departmentsSource[hid] || [];
                    departmentSelect = E.createSearchableSelect(document.getElementById("ss-user-department"), depts, {
                        placeholder: hid ? "اختر القسم..." : "اختر المستشفى أولاً...",
                    });
                }

                function initUserSelects(hospitalId, departmentId) {
                    hospitalSelect = E.createSearchableSelect(document.getElementById("ss-user-hospital"), D.hospitals, {
                        placeholder: "ابحث واختر المستشفى...",
                        onSelect: handleUserHospitalSelect,
                        value: hospitalId ? String(hospitalId) : "",
                    });
                    if (hospitalId) {
                        const hid = String(hospitalId);
                        const depts = D.departmentsSource[hid] || [];
                        departmentSelect = E.createSearchableSelect(document.getElementById("ss-user-department"), depts, {
                            placeholder: "اختر القسم...",
                            value: departmentId ? String(departmentId) : "",
                        });
                    } else {
                        departmentSelect = E.createSearchableSelect(document.getElementById("ss-user-department"), [], { placeholder: "اختر المستشفى أولاً..." });
                    }
                }

                const columns = [
                    { key: "idx", header: "مسلسل", width: "70px", render: (r, i, p) => (p.from || 1) + i },
                    { key: "name", header: "الاسم", render: (r) => `<span class="font-semibold text-foreground">${r.name}</span>` },
                    { key: "username", header: "اسم المستخدم", render: (r) => `<span class="text-muted-foreground">@${r.username}</span>` },
                    { key: "role", header: "الصلاحية", render: (r) => r.isAdmin
                        ? `<span class="rounded-md bg-brand/15 px-2 py-1 text-[12px] font-semibold text-primary">مدير</span>`
                        : `<span class="rounded-md bg-muted px-2 py-1 text-[12px] font-semibold text-muted-foreground">مستخدم</span>` },
                    { key: "category", header: "فئة المستخدم", render: (r) => r.category
                        ? `<span class="rounded-md bg-accent px-2 py-1 text-[12px] font-semibold text-accent-foreground" style="white-space:nowrap">${r.category}</span>`
                        : `<span class="text-muted-foreground">—</span>` },
                    { key: "actions", header: "الإجراءات", width: "120px", render: (r) => r.actions },
                ];

                function renderTable() {
                    currentTable = E.renderServerTable(document.getElementById("users-table"), columns, {
                        loadUrl: D.loadUrl,
                        pageSize: 10,
                        exportName: "المستخدمون",
                        searchable: true,
                        minWidth: "",
                        onLoaded: (payload) => syncUsersFromRows(payload.rows || []),
                    });
                }

                function syncUsersFromRows(rows) {
                    if (!rows.length) return;
                    D.users = rows.map((row) => ({
                        id: row.id,
                        name: row.name,
                        username: row.username,
                        is_admin: row.isAdmin,
                        category: row.category,
                        hospital_id: row.hospitalId,
                        department_id: row.departmentId,
                        input_permissions: row.inputPermissions || null,
                    }));
                }

const supplyOn = document.getElementById("perm-supply-on");
                    const permitsOn = document.getElementById("perm-permits-on");
                    const supplyFields = document.getElementById("perm-supply-fields");
                    const permitsFields = document.getElementById("perm-permits-fields");

                    supplyFields.classList.add("hidden");
                    permitsFields.classList.add("hidden");

                    supplyOn.addEventListener("change", () => {
                        supplyFields.classList.toggle("hidden", !supplyOn.checked);
                        supplyFields.querySelectorAll(".perm-supply-cb").forEach(cb => { cb.disabled = !supplyOn.checked; if (!supplyOn.checked) cb.checked = false; });
                    });
                    permitsOn.addEventListener("change", () => {
                        permitsFields.classList.toggle("hidden", !permitsOn.checked);
                        permitsFields.querySelectorAll(".perm-permits-cb").forEach(cb => { cb.disabled = !permitsOn.checked; if (!permitsOn.checked) cb.checked = false; });
                    });

                    function collectPermissions() {
                        const perms = {};
                        if (supplyOn.checked) {
                            const fields = [];
                            supplyFields.querySelectorAll(".perm-supply-cb:checked").forEach(cb => fields.push(cb.value));
                            perms.supply_requests = { permitted: true, fields };
                        }
                        if (permitsOn.checked) {
                            const fields = [];
                            permitsFields.querySelectorAll(".perm-permits-cb:checked").forEach(cb => fields.push(cb.value));
                            perms.priced_delivery_permits = { permitted: true, fields };
                        }
                        return Object.keys(perms).length ? perms : null;
                    }

                    function resetPermissions() {
                        supplyOn.checked = false;
                        permitsOn.checked = false;
                        supplyFields.classList.add("hidden");
                        permitsFields.classList.add("hidden");
                        supplyFields.querySelectorAll(".perm-supply-cb").forEach(cb => { cb.disabled = true; cb.checked = false; });
                        permitsFields.querySelectorAll(".perm-permits-cb").forEach(cb => { cb.disabled = true; cb.checked = false; });
                    }

                    function fillPermissions(perms) {
                        resetPermissions();
                        if (!perms) return;
                        if (perms.supply_requests && perms.supply_requests.permitted) {
                            supplyOn.checked = true;
                            supplyFields.classList.remove("hidden");
                            const allowed = new Set(perms.supply_requests.fields || []);
                            supplyFields.querySelectorAll(".perm-supply-cb").forEach(cb => { cb.disabled = false; cb.checked = allowed.has(cb.value); });
                        }
                        if (perms.priced_delivery_permits && perms.priced_delivery_permits.permitted) {
                            permitsOn.checked = true;
                            permitsFields.classList.remove("hidden");
                            const allowed = new Set(perms.priced_delivery_permits.fields || []);
                            permitsFields.querySelectorAll(".perm-permits-cb").forEach(cb => { cb.disabled = false; cb.checked = allowed.has(cb.value); });
                        }
                    }

                    /* ---------- فتح المودال ---------- */
                    function resetForm() {
                        editingId = null;
                        document.getElementById("modal-title").textContent = "إضافة مستخدم";
                        document.getElementById("user-form").reset();
                        document.getElementById("password-hint").style.display = "none";
                        initUserSelects(null, null);
                        resetPermissions();
                    }

                document.getElementById("add-btn").addEventListener("click", () => {
                    resetForm();
                    E.openModal("user-modal");
                });

                /* ---------- تحميل بيانات للتعديل ---------- */
                document.addEventListener("click", (e) => {
                    const btn = e.target.closest("[data-edit]");
                    if (!btn) return;
                    const id = parseInt(btn.getAttribute("data-edit"), 10);
                    const u = D.users.find((x) => x.id === id);
                    if (!u) return;

                    editingId = id;
                    document.getElementById("modal-title").textContent = "تعديل مستخدم";
                    document.getElementById("f-name").value = u.name;
                    document.getElementById("f-username").value = u.username;
                    document.getElementById("f-password").value = "";
                    document.getElementById("password-hint").style.display = "block";
                    document.getElementById("f-is-admin").checked = !!u.is_admin;
                    document.getElementById("f-category").value = u.category || "";
                    initUserSelects(u.hospital_id, u.department_id);
                    fillPermissions(u.input_permissions);
                    E.openModal("user-modal");
                });

                /* ---------- الحفظ ---------- */
                document.getElementById("user-form").addEventListener("submit", async (e) => {
                    e.preventDefault();
                    const form = e.currentTarget;
                    E.clearFieldErrors(form);

                    const data = {
                        name: document.getElementById("f-name").value.trim(),
                        username: document.getElementById("f-username").value.trim(),
                        password: document.getElementById("f-password").value,
                        is_admin: document.getElementById("f-is-admin").checked ? 1 : 0,
                        category: document.getElementById("f-category").value,
                        hospital_id: document.getElementById("ss-user-hospital").dataset.value || null,
                        department_id: document.getElementById("ss-user-department").dataset.value || null,
                        input_permissions: collectPermissions(),
                    };

                    const btn = document.getElementById("save-btn");
                    btn.disabled = true;
                    const original = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner"></span> جارٍ الحفظ...';

                    try {
                        const url = editingId ? D.updateUrl.replace("__ID__", editingId) : D.storeUrl;
                        const method = editingId ? "PUT" : "POST";
                        const res = await E.request(url, { method, body: data });

                        if (res.ok === false) {
                            E.toast(res.message || "تعذر الحفظ.", "error");
                            return;
                        }

                        E.toast(res.data.message || "تم الحفظ بنجاح.", "success");
                        E.closeModal("user-modal");
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
