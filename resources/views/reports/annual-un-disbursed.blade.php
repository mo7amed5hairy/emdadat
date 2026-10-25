@extends('layouts.app')

@section('title', 'تقرير سنوى لما لم يتم صرفه')
@section('description', 'عرض تقرير سنوى للأذونات التى لم يتم صرفها حسب الشهر والمستخدم ونوع الطلب.')

@section('page-title', 'تقرير سنوى لما لم يتم صرفه')
@section('page-subtitle', 'عرض الأذونات حسب الشهر والمستخدم ونوع الطلب مع إجماليات شهرية وسنوية')

@section('content')
    <div class="mb-5 rounded-xl border border-border bg-card p-5 shadow-soft">
        <div class="mb-4 flex items-center gap-2">
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand/10 text-primary">
                <i data-lucide="calendar-x2" class="h-[18px] w-[18px]"></i>
            </span>
            <h2 class="text-sm font-bold text-foreground">معايير البحث</h2>
        </div>
        <form method="GET" action="{{ route('reports.annual-un-disbursed') }}" id="report-form"
            class="grid gap-4 md:grid-cols-3">
            <div class="space-y-1.5">
                <label class="block text-[13px] font-semibold text-foreground/85">حالة الصرف</label>
                <div class="relative">
                    <select name="status"
                        class="h-10 w-full appearance-none rounded-lg border border-input bg-card ps-3 pe-9 text-[13px] text-foreground outline-none transition-shadow focus:border-primary focus:ring-2 focus:ring-ring/35">
                        <option value="لم يتم الصرف" {{ $status === 'لم يتم الصرف' ? 'selected' : '' }}>لم يتم الصرف</option>
                        <option value="تم الصرف" {{ $status === 'تم الصرف' ? 'selected' : '' }}>تم الصرف</option>
                    </select>
                    <i data-lucide="chevron-down"
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                </div>
            </div>
            <div class="space-y-1.5">
                <label class="block text-[13px] font-semibold text-foreground/85">السنة</label>
                <div class="relative">
                    <select name="year"
                        class="h-10 w-full appearance-none rounded-lg border border-input bg-card ps-3 pe-9 text-[13px] text-foreground outline-none transition-shadow focus:border-primary focus:ring-2 focus:ring-ring/35">
                        <option value="">كل السنوات</option>
                        @foreach ($years as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                    <i data-lucide="chevron-down"
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                </div>
            </div>
            <div class="space-y-1.5">
                <label class="block text-[13px] font-semibold text-foreground/85">الشهر</label>
                <div class="relative">
                    <select name="month"
                        class="h-10 w-full appearance-none rounded-lg border border-input bg-card ps-3 pe-9 text-[13px] text-foreground outline-none transition-shadow focus:border-primary focus:ring-2 focus:ring-ring/35">
                        <option value="">كل الشهور</option>
                        @foreach ($arMonths as $num => $arName)
                            <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $arName }}</option>
                        @endforeach
                    </select>
                    <i data-lucide="chevron-down"
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                </div>
            </div>
            <div class="space-y-1.5">
                <label class="block text-[13px] font-semibold text-foreground/85">الارتباط المالي</label>
                <div class="relative">
                    <select name="financial_link" id="f-financial-link"
                        class="h-10 w-full appearance-none rounded-lg border border-input bg-card ps-3 pe-9 text-[13px] text-foreground outline-none transition-shadow focus:border-primary focus:ring-2 focus:ring-ring/35">
                        <option value="">كل الارتباطات المالية</option>
                        @foreach ($financialLinks as $link)
                            <option value="{{ $link->name }}" {{ $financialLink === $link->name ? 'selected' : '' }}>{{ $link->name }}</option>
                        @endforeach
                    </select>
                    <i data-lucide="chevron-down"
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                </div>
            </div>
            <div class="space-y-1.5">
                <label class="block text-[13px] font-semibold text-foreground/85">نوع الارتباط المالي</label>
                <div class="relative">
                    <select name="financial_category" id="f-financial-category"
                        class="h-10 w-full appearance-none rounded-lg border border-input bg-card ps-3 pe-9 text-[13px] text-foreground outline-none transition-shadow focus:border-primary focus:ring-2 focus:ring-ring/35">
                        <option value="">كل الأنواع</option>
                    </select>
                    <i data-lucide="chevron-down"
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                </div>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit"
                    class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand px-5 text-[13px] font-bold text-primary-foreground shadow-soft transition-transform hover:-translate-y-0.5">
                    <i data-lucide="search" class="h-4 w-4"></i> بحث
                </button>
                <a href="{{ route('reports.annual-un-disbursed') }}" title="إعادة تعيين"
                    class="grid h-10 w-10 place-items-center rounded-lg border border-border bg-card text-muted-foreground transition-colors hover:bg-accent">
                    <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                </a>
                <button type="button" onclick="window.print()"
                    class="inline-flex h-10 items-center gap-2 rounded-lg border border-border bg-card px-5 text-[13px] font-bold text-muted-foreground transition-colors hover:bg-accent">
                    <i data-lucide="printer" class="h-4 w-4"></i> طباعة
                </button>
            </div>
        </form>
    </div>

    <div class="report-container mb-8">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div></div>
            <div class="text-center text-[22px] font-bold text-foreground">
                استمارات لدى إدارة التوريدات حتى {{ $now->format('d/m/Y') }}
            </div>
            <button type="button" data-export-excel title="تصدير Excel"
                class="data-export-excel inline-flex h-10 items-center gap-2 rounded-lg border border-border bg-card px-5 text-[13px] font-bold text-muted-foreground transition-colors hover:bg-accent">
                <i data-lucide="file-down" class="h-4 w-4"></i>
                تصدير Excel
            </button>
        </div>

        @if (empty($monthOrder))
            <div class="rounded-xl border border-border bg-card p-8 text-center shadow-soft">
                <p class="text-[14px] font-semibold text-muted-foreground">لا توجد بيانات تطابق معايير البحث المحددة.</p>
            </div>
        @else
            @php
                $totalTypeCols = 0;
                foreach ($typeGroups as $tgData) {
                    $totalTypeCols += count($tgData['users']) + 1;
                }
            @endphp
            <div class="overflow-x-auto rounded-xl border border-border shadow-soft">
                <table class="main-table w-full border-collapse text-[13px]" style="table-layout:fixed">
                    <thead>
                        <tr>
                            <th class="bg-muted/50 border border-border px-3 py-2.5 text-center text-[13px] font-bold"
                                style="width:12%"></th>
                            @foreach ($typeGroups as $tgKey => $tgData)
                                <th colspan="{{ count($tgData['users']) + 1 }}"
                                    class="bg-muted/50 border border-border px-3 py-2.5 text-center text-[13px] font-bold">
                                    {{ $tgData['label'] }}
                                </th>
                            @endforeach
                        </tr>
                        <tr>
                            <th class="bg-muted/50 border border-border px-3 py-2.5 text-center text-[13px] font-bold"
                                style="width:12%">الشهر</th>
                            @foreach ($typeGroups as $tgKey => $tgData)
                                @foreach ($tgData['users'] as $userName)
                                    <th class="bg-muted/50 border border-border px-2 py-2 text-center text-[12px] font-bold"
                                        style="width:auto">{{ $userName }}</th>
                                @endforeach
                                <th class="bg-muted/50 border border-border px-2 py-2 text-center text-[12px] font-bold"
                                    style="width:auto">إجمالي الشهر</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($monthOrder as $mk => $ml)
                            <tr>
                                <td class="border border-border px-3 py-2 text-center text-[13px] font-bold">{{ $ml }}</td>
                                @foreach ($typeGroups as $tgKey => $tgData)
                                    @foreach ($tgData['users'] as $userName)
                                        @php $val = $cells[$mk][$tgKey][$userName] ?? 0; @endphp
                                        <td class="number-cell border border-border px-2 py-2 text-center text-[13px]" dir="ltr">
                                            {{ $val ? number_format($val, 2) : '-' }}
                                        </td>
                                    @endforeach
                                    @php $sub = 0;
                                        foreach ($tgData['users'] as $u) {
                                            $sub += $cells[$mk][$tgKey][$u] ?? 0;
                                    } @endphp
                                    <td class="number-cell border border-border px-2 py-2 text-center text-[13px] font-bold" dir="ltr">
                                        {{ $sub ? number_format($sub, 2) : '-' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        <tr class="bg-muted/30 font-bold">
                            <td class="border border-border px-3 py-2.5 text-center text-[13px]">الإجمالي</td>
                            @foreach ($typeGroups as $tgKey => $tgData)
                                @foreach ($tgData['users'] as $userName)
                                    @php $colTotal = 0;
                                        foreach ($monthOrder as $mk2 => $_) {
                                            $colTotal += $cells[$mk2][$tgKey][$userName] ?? 0;
                                    } @endphp
                                    <td class="number-cell border border-border px-2 py-2.5 text-center text-[13px] font-bold"
                                        dir="ltr">
                                        {{ $colTotal ? number_format($colTotal, 2) : '-' }}
                                    </td>
                                @endforeach
                                <td class="number-cell border border-border px-2 py-2.5 text-center text-[13px] font-bold"
                                    dir="ltr">
                                    {{ ($categoryTotals[$tgKey] ?? 0) ? number_format($categoryTotals[$tgKey], 2) : '-' }}
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-10 mx-auto" style="max-width:75%">
                <table class="w-full border-collapse text-[16px] font-bold" style="direction:rtl">
                    @foreach ($typeGroups as $tgKey => $tgData)
                        <tr>
                            <td class="border border-border px-4 py-3 text-right bg-muted/50" style="width:50%">
                                {{ $tgData['label'] }}</td>
                            <td class="border border-border px-4 py-3 text-center" style="width:50%;direction:ltr">
                                {{ number_format($categoryTotals[$tgKey] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="text-[20px]">
                        <td class="border border-border px-4 py-3 text-right bg-muted/50 font-extrabold" style="width:50%">
                            الإجمالي</td>
                        <td class="border border-border px-4 py-3 text-center font-extrabold" style="width:50%;direction:ltr">
                            {{ number_format($grandTotal, 2) }}</td>
                    </tr>
                </table>
            </div>

            <div class="mt-12 text-right text-[14px] text-muted-foreground" style="direction:ltr">
                {{ $now->format('d/m/Y') }}
            </div>
        @endif
    </div>

    <style>
        .report-container .main-table th,
        .report-container .main-table td {
            border: 1px solid var(--color-border);
            height: 38px;
        }

        .number-cell {
            direction: ltr;
            text-align: center !important;
            white-space: nowrap;
        }

        @media print {
            body {
                padding: 10mm;
            }

            .report-container {
                max-width: none;
            }

            .report-container .main-table th,
            .report-container .main-table td {
                font-size: 12px;
                padding: 4px 2px;
            }

            .mb-5 {
                display: none !important;
            }
        }
    </style>
@endsection

@push('scripts')
    <script>
        (function () {
            const linksData = {!! $financialLinks->map(fn ($l) => ['name' => $l->name, 'types' => $l->types->pluck('name')->all()])->toJson() !!};
            const selectedLink = @json($financialLink);
            const selectedCategory = @json($financialCategory);

            const boot = function () {
                if (!window.emdadat) return;

                /* الارتباط المالي → قائمة أنواع الارتباط المنبثقة التابعة له */
                const linkSelect = document.getElementById('f-financial-link');
                const categorySelect = document.getElementById('f-financial-category');

                function buildCategoryOptions(keepValue) {
                    if (!categorySelect) return;
                    const linkName = linkSelect ? linkSelect.value : '';
                    const found = linksData.find((l) => l.name === linkName);
                    const types = found ? (found.types || []) : [];
                    categorySelect.innerHTML = '<option value="">كل الأنواع</option>' +
                        types.map((t) => `<option value="${t}">${t}</option>`).join('');
                    if (keepValue && types.includes(keepValue)) {
                        categorySelect.value = keepValue;
                    }
                }

                if (linkSelect && categorySelect) {
                    buildCategoryOptions(selectedCategory);
                    linkSelect.addEventListener('change', () => buildCategoryOptions());
                }

                const btn = document.querySelector('[data-export-excel]');
                if (!btn) return;

                btn.addEventListener('click', async function () {
                    btn.disabled = true;
                    const prev = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner"></span>';
                    try {
                        const form = document.getElementById('report-form');
                        const params = new URLSearchParams({
                            export: 1,
                            status: form.querySelector('[name=status]').value,
                            year: form.querySelector('[name=year]').value,
                            month: form.querySelector('[name=month]').value,
                            financial_link: form.querySelector('[name=financial_link]').value,
                            financial_category: form.querySelector('[name=financial_category]').value,
                        });
                        const res = await fetch(window.location.pathname + '?' + params.toString(), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        if (res.status === 401) { window.location.href = '/login'; return; }
                        if (!res.ok) throw new Error('export failed');
                        const payload = await res.json();
                        const headers = payload.headers || [];
                        const rows = payload.rows || [];
                        window.emdadat.downloadCsv(
                            'annual-un-disbursed-' + new Date().toISOString().slice(0, 10) + '.csv',
                            headers,
                            rows,
                        );
                        window.emdadat.toast('تم تصدير ' + rows.length + ' صف بنجاح.', 'success');
                    } catch (err) {
                        window.emdadat.toast('تعذر التصدير، حاول مرة أخرى.', 'error');
                    } finally {
                        btn.innerHTML = prev;
                        btn.disabled = false;
                        window.lucide.createIcons();
                    }
                });
            };
            document.addEventListener('DOMContentLoaded', boot);
        })();
    </script>
@endpush