@extends('layouts.app')

@section('title', 'تقارير أذون التسليم')
@section('description', 'تصفية وعرض تقارير أذون التسليم المسعّرة حسب الشهر والسنة والشركة.')

@section('page-title', 'تقارير أذون التسليم')
@section('page-subtitle', 'تصفية وعرض تقارير أذون التسليم المسعّرة للشركات')

@section('content')
    @php
        $months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
        $monthVal = (int) (request('month') ?? 0);
        $yearVal = request('year') ?? '';
        $companyVal = request('company_id') ?? '';
        $dateFromVal = request('date_from') ?? '';
        $dateToVal = request('date_to') ?? '';
        $hospitalVal = request('hospital_id') ?? '';
        $departmentVal = request('department_id') ?? '';
    @endphp

    <!-- بطاقة معايير البحث -->
    <div class="mb-5 rounded-xl border border-border bg-card p-5 shadow-soft">
        <div class="mb-4 flex items-center gap-2">
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand/10 text-primary">
                <i data-lucide="clipboard-list" class="h-[18px] w-[18px]"></i>
            </span>
            <h2 class="text-sm font-bold text-foreground">معايير البحث</h2>
        </div>

        <form method="GET" action="{{ route('reports.priced-delivery-permits') }}" id="report-form" class="grid gap-4 md:grid-cols-4">
            <div class="space-y-1.5">
                <label class="block text-[13px] font-semibold text-foreground/85">تاريخ من</label>
                <input type="date" name="date_from" value="{{ $dateFromVal }}"
                    class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow focus:border-primary focus:ring-2 focus:ring-ring/35" />
            </div>

            <div class="space-y-1.5">
                <label class="block text-[13px] font-semibold text-foreground/85">تاريخ إلى</label>
                <input type="date" name="date_to" value="{{ $dateToVal }}"
                    class="h-10 w-full rounded-lg border border-input bg-card px-3 text-[13px] text-foreground outline-none transition-shadow focus:border-primary focus:ring-2 focus:ring-ring/35" />
            </div>

            <div class="space-y-1.5">
                <label class="block text-[13px] font-semibold text-foreground/85">الشهر <span class="text-[11px] text-muted-foreground">(بحث)</span></label>
                <div id="ss-report-month"></div>
                <input type="hidden" name="month" id="hid-month" value="{{ $monthVal ?: '' }}" />
            </div>

            <div class="space-y-1.5">
                <label class="block text-[13px] font-semibold text-foreground/85">السنة <span class="text-[11px] text-muted-foreground">(بحث)</span></label>
                <div id="ss-report-year"></div>
                <input type="hidden" name="year" id="hid-year" value="{{ $yearVal }}" />
            </div>

            <div class="space-y-1.5">
                <label class="block text-[13px] font-semibold text-foreground/85">المستشفى <span class="text-[11px] text-muted-foreground">(بحث)</span></label>
                <div id="ss-report-hospital"></div>
                <input type="hidden" name="hospital_id" id="hid-hospital" value="{{ $hospitalVal }}" />
            </div>

            <div class="space-y-1.5">
                <label class="block text-[13px] font-semibold text-foreground/85">القسم <span class="text-[11px] text-muted-foreground">(اختر المستشفى أولاً)</span></label>
                <div id="ss-report-department"></div>
                <input type="hidden" name="department_id" id="hid-department" value="{{ $departmentVal }}" />
            </div>

            <div class="space-y-1.5">
                <label class="block text-[13px] font-semibold text-foreground/85">اسم الشركة <span class="text-[11px] text-muted-foreground">(بحث)</span></label>
                <div id="ss-report-company"></div>
                <input type="hidden" name="company_id" id="hid-company" value="{{ $companyVal }}" />
            </div>

            <div class="flex flex-wrap items-center gap-2 md:col-span-4">
                <button type="submit"
                    class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand px-5 text-[13px] font-bold text-primary-foreground shadow-soft transition-transform hover:-translate-y-0.5">
                    <i data-lucide="search" class="h-4 w-4"></i>
                    بحث
                </button>
                <a href="{{ route('reports.priced-delivery-permits') }}"
                    class="inline-flex h-10 items-center gap-2 rounded-lg border border-border bg-card px-5 text-[13px] font-bold text-muted-foreground transition-colors hover:bg-accent">
                    <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                    إعادة تعيين
                </a>
                <button type="submit" name="export" value="1"
                    class="inline-flex h-10 items-center gap-2 rounded-lg border border-border bg-card px-5 text-[13px] font-bold text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">
                    <i data-lucide="file-down" class="h-4 w-4"></i>
                    تصدير Excel
                </button>
            </div>
        </form>
    </div>

    <!-- النتائج -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-sm font-bold text-foreground">نتائج البحث</h2>
        <div class="flex items-center gap-3">
            @if ($hasFilters)
                <span class="inline-flex items-center gap-1 rounded-md bg-brand/10 px-2 py-1 text-[12px] font-semibold text-primary">
                    <i data-lucide="circle-check-big" class="h-3.5 w-3.5"></i>
                    الإجمالي: {{ number_format($totalAmount, 2) }} ج.م
                </span>
            @endif
            <p id="result-count" class="text-xs text-muted-foreground">
                {{ $hasFilters && $resultCount > 0 ? 'عدد النتائج: ' . number_format($resultCount) . ' سجل' : '' }}
            </p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-border bg-card shadow-card">
        <div class="scroll-slim overflow-x-auto">
            <table class="w-full min-w-[980px] border-collapse text-right text-[13px]">
                <thead>
                    <tr class="bg-brand text-primary-foreground">
                        <th class="whitespace-nowrap px-4 py-3 text-right text-[13px] font-semibold" style="width:80px">مسلسل</th>
                        <th class="whitespace-nowrap px-4 py-3 text-right text-[13px] font-semibold">رقم إذن التسليم</th>
                        <th class="whitespace-nowrap px-4 py-3 text-right text-[13px] font-semibold">رقم الإمداد</th>
                        <th class="whitespace-nowrap px-4 py-3 text-right text-[13px] font-semibold">قيمة إذن التسليم</th>
                        <th class="whitespace-nowrap px-4 py-3 text-right text-[13px] font-semibold">تاريخ إصدار الإذن</th>
                        <th class="whitespace-nowrap px-4 py-3 text-right text-[13px] font-semibold">اسم الشركة</th>
                        <th class="whitespace-nowrap px-4 py-3 text-right text-[13px] font-semibold">تاريخ تحرير الاستمارة/الصرف</th>
                        <th class="whitespace-nowrap px-4 py-3 text-right text-[13px] font-semibold">حالة الإذن</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="border-t border-border/70 transition-colors odd:bg-card even:bg-muted/40 hover:bg-accent/60">
                            <td class="whitespace-nowrap px-4 py-3 text-foreground/90">{{ $row['idx'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-foreground/90">{{ $row['noteNo'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-foreground/90">{{ $row['supplyNo'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><span class="font-semibold text-primary">{{ number_format($row['amount'], 2) }} ج.م</span></td>
                            <td class="whitespace-nowrap px-4 py-3 text-foreground/90">{{ $row['issueDate'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-foreground/90">{{ $row['company'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-foreground/90">{{ $row['formDate'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @php $done = $row['status'] === 'تم الصرف'; @endphp
                                <span class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-[12px] font-semibold {{ $done ? 'bg-success/15 text-success' : 'bg-gold/20 text-gold-foreground' }}">
                                    <i data-lucide="badge-check" class="h-3.5 w-3.5"></i>
                                    {{ $row['status'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-14 text-center">
                                <i data-lucide="inbox" class="mx-auto mb-3 h-8 w-8 text-muted-foreground/60"></i>
                                <p class="text-sm text-muted-foreground">{{ $hasFilters ? 'لا توجد بيانات مطابقة لمعايير البحث' : 'اضغط على زر البحث لعرض النتائج' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-border bg-muted/40 px-4 py-3">
            <p class="text-xs text-muted-foreground">عرض {{ $paginator->count() }} من إجمالي {{ $paginator->total() }} سجل</p>
            {{ $paginator->onEachSide(2)->links('vendor.pagination.emdadat') }}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const boot = function () {
            const E = window.emdadat;
            if (!E) return;

            const months = {!! collect($months)->map(fn ($m, $i) => ['value' => $i + 1, 'label' => $m])->values()->toJson() !!};
            const years = {!! collect($years)->map(fn ($y) => ['value' => $y, 'label' => $y])->toJson() !!};
            const hospitals = {!! $hospitals->map(fn ($h) => ['value' => $h->id, 'label' => $h->name])->values()->toJson() !!};
            const departmentsSource = {!! $departmentsSource->toJson() !!};
            const companies = {!! $companies->map(fn ($c) => ['value' => $c->id, 'label' => $c->name])->values()->toJson() !!};

            const hidMonth = document.getElementById("hid-month");
            const hidYear = document.getElementById("hid-year");
            const hidHospital = document.getElementById("hid-hospital");
            const hidDepartment = document.getElementById("hid-department");
            const hidCompany = document.getElementById("hid-company");

            let departmentRef = null;

            E.createSearchableSelect(document.getElementById("ss-report-month"), months, {
                placeholder: "كل الشهور",
                value: hidMonth.value,
            });
            E.createSearchableSelect(document.getElementById("ss-report-year"), years, {
                placeholder: "كل السنوات",
                value: hidYear.value,
            });
            E.createSearchableSelect(document.getElementById("ss-report-hospital"), hospitals, {
                placeholder: "كل المستشفيات",
                value: hidHospital.value,
                onSelect: () => rebuildDepartments(),
            });

            /* قسم: يظهر فقط أقسام المستشفى المختارة */
            function rebuildDepartments() {
                const hospId = document.getElementById("ss-report-hospital").dataset.value || "";
                const depts = hospId ? (departmentsSource[hospId] || []) : [];
                departmentRef = E.createSearchableSelect(document.getElementById("ss-report-department"), depts, {
                    placeholder: hospId ? "كل أقسام المستشفى" : "اختر المستشفى أولاً...",
                    value: hospId === hidHospital.value ? hidDepartment.value : "",
                });
            }
            rebuildDepartments();

            E.createSearchableSelect(document.getElementById("ss-report-company"), companies, {
                placeholder: "كل الشركات",
                value: hidCompany.value,
            });

            const form = document.getElementById("report-form");
            form.addEventListener("submit", () => {
                hidMonth.value = document.getElementById("ss-report-month").dataset.value || "";
                hidYear.value = document.getElementById("ss-report-year").dataset.value || "";
                hidHospital.value = document.getElementById("ss-report-hospital").dataset.value || "";
                hidDepartment.value = document.getElementById("ss-report-department").dataset.value || "";
                hidCompany.value = document.getElementById("ss-report-company").dataset.value || "";
            });
            };
            document.addEventListener('DOMContentLoaded', boot);
        })();
    </script>
@endpush