@extends('layouts.app')

@section('title', 'أذون تسليم مسعّر')
@section('description', 'جدول متابعة أذون التسليم المسعّرة مع إضافة إذن تسليم جديد.')

@section('page-title', 'أذون تسليم مسعّر')
@section('page-subtitle', 'متابعة وتسجيل أذون التسليم المسعّرة الصادرة للشركات')

@section('content')
    @php
        $companiesJson = $companies->map(fn ($c) => ['value' => $c->id, 'label' => $c->name]);
        $usersJson = $users->map(fn ($u) => ['value' => $u->id, 'label' => $u->name]);
        $total = \App\Models\PricedDeliveryPermit::count();
        $totalAmount = \App\Models\PricedDeliveryPermit::sum('delivery_amount');
        $done = \App\Models\PricedDeliveryPermit::where('status', 'تم الصرف')->count();
        $pending = \App\Models\PricedDeliveryPermit::where('status', '!=', 'تم الصرف')->count();
    @endphp

    <!-- بطاقات الإحصائيات -->
    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand/10 text-primary">
                <i data-lucide="receipt" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">إجمالي الأذون</p>
                <p id="stat-total" class="text-lg font-extrabold text-foreground">{{ number_format($total) }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-gold/15 text-gold-foreground">
                <i data-lucide="hand-coins" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">إجمالي قيم الأذون</p>
                <p id="stat-total-amount" class="text-lg font-extrabold text-foreground">{{ number_format($totalAmount, 2) }} ج.م</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-success/15 text-success">
                <i data-lucide="badge-check" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">أذون تم صرفها</p>
                <p id="stat-done" class="text-lg font-extrabold text-foreground">{{ number_format($done) }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-gold/20 text-gold-foreground">
                <i data-lucide="clock" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">أذون لم يتم صرفها</p>
                <p id="stat-pending" class="text-lg font-extrabold text-foreground">{{ number_format($pending) }}</p>
            </div>
        </div>
    </div>

    <!-- رأس الجدول وزر الإضافة -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-sm font-bold text-foreground">سجل أذون التسليم المسعّرة</h2>
        <button type="button" id="add-btn"
            class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand px-4 text-[13px] font-bold text-primary-foreground shadow-soft transition-transform hover:-translate-y-0.5">
            <i data-lucide="file-plus" class="h-4 w-4"></i>
            إضافة إذن تسليم مسعّر
        </button>
    </div>

    <!-- الجدول -->
    <div id="notes-table"></div>

    <!-- ===== مودال إضافة إذن تسليم مسعّر ===== -->
    @include('priced-delivery-permits._add-modal', ['idPrefix' => ''])

    <script>
        window.emdadat_data = {
            companies: {!! $companiesJson->toJson() !!},
            users: {!! $usersJson->toJson() !!},
            currentUserId: {{ auth()->id() ?? 'null' }},
            loadUrl: "{{ route('priced-delivery-permits.index') }}",
            storeUrl: "{{ route('priced-delivery-permits.store') }}",
            updateUrl: "{{ route('priced-delivery-permits.update', ':id') }}",
            lookupUrl: "{{ route('supply-requests.lookup') }}",
            companiesQuickAddUrl: "{{ route('companies.quick-add') }}",
            permittedFields: {!! json_encode($permittedFields) !!},
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
            let companySelectRef = null;
            let userSelectRef = null;

            function initUserSelect(value) {
                userSelectRef = E.createSearchableSelect(document.getElementById("ss-user"), D.users, {
                    placeholder: "ابحث واختر المستخدم...",
                    value: value ? String(value) : "",
                });
            }

            const permitFieldMap = {
                delivery_amount: "f-amount",
                supply_request_number: "f-supply-no",
                delivery_permit_number: "f-note-no",
                issue_date: "f-issue-date",
                company_id: "ss-company",
                form_date: "f-form-date",
                status: "f-status",
                fully_delivered: "f-fully-delivered",
            };

            function applyUserOwnerVisibility() {
                /* المستخدم الكامل (input_permissions = null): ميدخلش انتخاب صاحب الطلب — الطلب يخصه هو */
                const wrap = document.getElementById("ss-user")?.closest(".field-wrap");
                if (wrap) wrap.style.display = D.permittedFields === null ? "none" : "";
            }

            function applyPermissions() {
                applyUserOwnerVisibility();
                const pf = D.permittedFields;
                if (!pf) return;
                const allowed = new Set(pf);
                Object.entries(permitFieldMap).forEach(([field, id]) => {
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

            function statusBadge(status) {
                const done = status === "تم الصرف";
                return (
                    `<span class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-[12px] font-semibold ${done ? "bg-success/15 text-success" : "bg-gold/20 text-gold-foreground"}">` +
                    E.icon("badge-check", "h-3.5 w-3.5") + status + "</span>"
                );
            }

            function updateStats() {
                const el = {
                    total: document.getElementById("stat-total"),
                    amount: document.getElementById("stat-total-amount"),
                    done: document.getElementById("stat-done"),
                    pending: document.getElementById("stat-pending"),
                };
                if (!el.total || !el.amount || !el.done || !el.pending) return;
                const payload = currentTable && currentTable._lastPayload;
                if (payload) {
                    el.total.textContent = Number(payload.total).toLocaleString('en-US');
                    el.amount.textContent = E.formatMoney(payload.grandTotal);
                    el.done.textContent = Number(payload.doneCount).toLocaleString('en-US');
                    el.pending.textContent = Number(payload.pendingCount).toLocaleString('en-US');
                }
            }

            const columns = [
                { key: "noteNo", header: "رقم إذن التسليم" },
                { key: "supplyNo", header: "رقم الإمداد" },
                { key: "amount", header: "قيمة إذن التسليم", render: (r) => `<span class="font-semibold text-primary">${E.formatMoney(r.amount)}</span>` },
                { key: "issueDate", header: "تاريخ إصدار الإذن" },
                { key: "company", header: "اسم الشركة" },
                { key: "formDate", header: "تاريخ تحرير الاستمارة/الصرف" },
                { key: "status", header: "حالة الإذن", render: (r) => statusBadge(r.status) },
                { key: "fullyDelivered", header: "حالة التوريد", render: (r) => r.fullyDelivered ? '<span class="rounded-md bg-success/15 px-2 py-1 text-[12px] font-semibold text-success">تم بالكامل</span>' : '<span class="text-muted-foreground/50">—</span>' },
                { key: "addedBy", header: "أُضيف بواسطة", render: (r) => `<span class="text-muted-foreground">${r.addedBy}</span>` },
                { key: "actions", header: "الإجراءات", width: "140px", render: (r) => r.actions },
            ];

            function renderTable() {
                currentTable = E.renderServerTable(document.getElementById("notes-table"), columns, {
                    loadUrl: D.loadUrl,
                    exportName: "أذونات الصرف المسعرة",
                    searchable: true,
                    onLoaded: (payload) => {
                        currentTable._lastPayload = payload;
                        updateStats();
                        window.lucide.createIcons();
                    },
                });
            }

            /* ---------- البحث عن طلب الإمداد برقمه ---------- */
            const supplyInput = document.getElementById("f-supply-no");
            const infoBox = document.getElementById("supply-info-box");
            let lastLookedUp = "";

            const SR_TEXT_FIELDS = [
                ["sr-type", "request_type"],
                ["sr-number", "supply_request_number"],
                ["sr-hospital", "hospital"],
                ["sr-department", "department"],
                ["sr-link", "financial_link"],
                ["sr-category", "financial_category"],
            ];

            function hideSupplyBox() {
                infoBox.classList.add("hidden");
                SR_TEXT_FIELDS.forEach(([id]) => (document.getElementById(id).value = ""));
                document.getElementById("sr-amount").value = "";
                document.getElementById("sr-date").value = "";
                document.getElementById("sr-permits-count").value = "";
                document.getElementById("sr-delivered").value = "";
                const rem = document.getElementById("sr-remaining");
                rem.value = "";
                rem.classList.remove("text-destructive");
                rem.classList.add("text-success");
            }

            function setRemainingColor(el, remaining) {
                const v = Math.round((Number(remaining) || 0) * 100) / 100;
                el.classList.toggle("text-success", v > 0);
                el.classList.toggle("text-destructive", v <= 0);
            }

            function fillSupplyBox(d) {
                SR_TEXT_FIELDS.forEach(([id, key]) => {
                    document.getElementById(id).value = d[key] || "—";
                });
                document.getElementById("sr-amount").value = E.formatMoney(d.amount);
                document.getElementById("sr-date").value = d.request_date || "—";
                document.getElementById("sr-permits-count").value = d.permits_count != null ? Number(d.permits_count).toLocaleString('en-US') : "—";
                document.getElementById("sr-delivered").value = E.formatMoney(d.delivered_amount);
                const remEl = document.getElementById("sr-remaining");
                remEl.value = E.formatMoney(d.remaining);
                setRemainingColor(remEl, d.remaining);
                infoBox.classList.remove("hidden");
            }

            function showNotFound(msg) {
                hideSupplyBox();
                Swal.fire({
                    icon: "error",
                    title: "غير موجود",
                    text: msg || "طلب الإمداد الذي بالرقم ده غير موجود.",
                    confirmButtonText: "حسنًا",
                    confirmButtonColor: "#b45309",
                });
            }

            async function lookupSupply(number) {
                try {
                    const res = await fetch(D.lookupUrl + "?" + new URLSearchParams({ number }), {
                        headers: { "Accept": "application/json", "X-Requested-With": "XMLHttpRequest" },
                    });
                    if (!res.ok) throw new Error("lookup failed");
                    const payload = await res.json();
                    if (payload.found) {
                        fillSupplyBox(payload.data);
                        window.lucide.createIcons();
                    } else {
                        showNotFound(payload.message);
                    }
                } catch (err) {
                    /* تجاهل أخطاء الشبكة المؤقتة */
                }
            }

            supplyInput.addEventListener("input", () => {
                const val = supplyInput.value.trim();
                if (!val) {
                    hideSupplyBox();
                    return;
                }
                if (val !== lastLookedUp) hideSupplyBox();
            });

            function triggerLookup() {
                const val = supplyInput.value.trim();
                if (!val) {
                    hideSupplyBox();
                    lastLookedUp = "";
                    return;
                }
                if (val === lastLookedUp) return;
                lastLookedUp = val;
                lookupSupply(val);
            }

            supplyInput.addEventListener("blur", triggerLookup);
            supplyInput.addEventListener("keydown", (e) => {
                if (e.key === "Enter") {
                    e.preventDefault();
                    triggerLookup();
                }
            });

            function resetSupplyLookup() {
                lastLookedUp = "";
                hideSupplyBox();
            }

            let editing = null;

            function getRow(id) {
                const payload = currentTable && currentTable._lastPayload;
                return payload ? payload.rows.find((r) => String(r.id) === String(id)) : null;
            }

            function setModalMode(isEdit) {
                document.getElementById("modal-title").textContent = isEdit ? "تعديل إذن تسليم مسعّر" : "إضافة إذن تسليم مسعّر";
                document.getElementById("modal-subtitle").textContent = isEdit ? "قم بتحديث بيانات إذن التسليم" : "قم بإدخال بيانات إذن التسليم المسعّر الجديد";
                document.getElementById("save-btn").innerHTML = isEdit
                    ? '<i data-lucide="save" class="h-4 w-4"></i> حفظ التعديلات'
                    : '<i data-lucide="save" class="h-4 w-4"></i> حفظ الإذن';
                window.lucide.createIcons();
            }

            function showViewModal(row) {
                const rows = [
                    ["رقم إذن التسليم", row.noteNo],
                    ["رقم الإمداد", row.supplyNo],
                    ["قيمة إذن التسليم", E.formatMoney(row.amount)],
                    ["تاريخ إصدار الإذن", row.issueDate],
                    ["اسم الشركة", row.company],
                    ["تاريخ تحرير الاستمارة/الصرف", row.formDate],
                    ["حالة الإذن", row.status],
                    ["حالة التوريد", row.fullyDelivered ? "تم بالكامل" : "—"],
                    ["أُضيف بواسطة", row.addedBy],
                ];
                const html = rows.map(([k, v]) =>
                    `<div style="display:flex;justify-content:space-between;gap:12px;padding:7px 10px;border-radius:8px;background:#f1f5f9;text-align:right"><span style="color:#64748b;font-size:13px;font-weight:600">${k}</span><span style="color:#0f172a;font-size:13px;font-weight:700">${v ?? "—"}</span></div>`
                ).join("");
                Swal.fire({
                    title: "بيانات إذن التسليم المسعّر",
                    html: `<div dir="rtl" style="display:grid;gap:6px">${html}</div>`,
                    width: 480,
                    confirmButtonText: "إغلاق",
                    confirmButtonColor: "#0f766e",
                });
            }

            document.addEventListener("click", (e) => {
                const viewBtn = e.target.closest("#notes-table [data-view]");
                if (viewBtn) {
                    const row = getRow(viewBtn.getAttribute("data-view"));
                    if (row) showViewModal(row);
                    return;
                }
                const editBtn = e.target.closest("#notes-table [data-edit]");
                if (!editBtn) return;
                const row = getRow(editBtn.getAttribute("data-edit"));
                if (!row) return;
                editing = row.id;
                document.getElementById("add-form").reset();
                resetSupplyLookup();
                document.getElementById("f-supply-no").value = row.supplyNo || "";
                lastLookedUp = row.supplyNo || "";
                document.getElementById("f-note-no").value = row.noteNo || "";
                document.getElementById("f-issue-date").value = row.issueDate || "";
                companySelectRef = E.createSearchableSelect(document.getElementById("ss-company"), D.companies, {
                    placeholder: "ابحث واختر الشركة...",
                    value: String(row.companyId || ""),
                });
                document.getElementById("f-form-date").value = row.formDate || "";
                document.getElementById("f-status").value = row.status || "";
                document.getElementById("f-fully-delivered").checked = !!row.fullyDelivered;
                document.getElementById("f-amount").value = row.amount != null ? Number(row.amount) : "";
                initUserSelect(row.assignedToId || "");
                setModalMode(true);
                applyPermissions();
                E.openModal("add-modal");
            });

            document.getElementById("add-btn").addEventListener("click", () => {
                editing = null;
                document.getElementById("add-form").reset();
                resetSupplyLookup();
                setModalMode(false);
                companySelectRef = E.createSearchableSelect(document.getElementById("ss-company"), D.companies, { placeholder: "ابحث واختر الشركة..." });
                initUserSelect(D.currentUserId || "");
                applyPermissions();
                E.openModal("add-modal");
            });

            const FIELD_IDS = {
                delivery_amount: "f-amount",
                supply_request_number: "f-supply-no",
                delivery_permit_number: "f-note-no",
                issue_date: "f-issue-date",
                company_id: "ss-company",
                form_date: "f-form-date",
                status: "f-status",
                fully_delivered: "f-fully-delivered",
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

                const data = {
                    assigned_to: document.getElementById("ss-user").dataset.value || "",
                    delivery_amount: document.getElementById("f-amount").value,
                    supply_request_number: document.getElementById("f-supply-no").value.trim(),
                    delivery_permit_number: document.getElementById("f-note-no").value.trim(),
                    issue_date: document.getElementById("f-issue-date").value,
                    company_id: document.getElementById("ss-company").dataset.value || "",
                    form_date: document.getElementById("f-form-date").value,
                    status: document.getElementById("f-status").value,
                    fully_delivered: document.getElementById("f-fully-delivered").checked ? 1 : 0,
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
                    E.toast(res.data.message || (editing ? "تم تعديل إذن التسليم بنجاح" : "تمت إضافة إذن التسليم بنجاح"), "success");
                    E.closeModal("add-modal");
                    document.getElementById("add-form").reset();
                    resetSupplyLookup();
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

            /* إضافة سريعة: شركة */
            E.attachQuickAdd("#ss-company", () => companySelectRef, {
                url: D.companiesQuickAddUrl,
                label: "الشركة",
                addLabel: "إضافة شركة جديدة",
                placeholder: "مثال: شركة الدواء الحديثة",
            });
            };
            document.addEventListener('DOMContentLoaded', boot);
        })();
    </script>
@endpush