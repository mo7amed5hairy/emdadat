@extends('layouts.app')

@section('title', 'لوحة التحكم')
@section('description', 'نظرة عامة على طلبات الإمداد وأذون التسليم المسعّرة.')

@section('page-title', 'لوحة التحكم')
@section('page-subtitle', 'نظرة عامة على حالة طلبات الإمداد وأذون التسليم')

@section('content')


    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-2">

        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand/10 text-primary">
                <i data-lucide="package-search" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">إجمالي الطلبات</p>
                <p class="text-lg font-extrabold text-foreground">{{ number_format($totalRequests) }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-gold/15 text-gold-foreground">
                <i data-lucide="wallet" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">إجمالي القيم المالية</p>
                <p class="text-lg font-extrabold text-foreground">{{ number_format($totalRequestsAmount, 2) }} ج.م</p>
            </div>
        </div>

    </div>

    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-2">


        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-success/15 text-success">
                <i data-lucide="clipboard-list" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">إجمالي طلبات الميزانية</p>
                <p class="text-lg font-extrabold text-success">{{ number_format($budgetRequests) }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-success/15 text-success">
                <i data-lucide="circle-dollar-sign" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">قيمة إجمالي طلبات الميزانية</p>
                <p class="text-lg font-extrabold text-success">{{ number_format($budgetRequestsAmount, 2) }} ج.م</p>
            </div>
        </div>

    </div>


    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-2">

        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-gold/15 text-gold-foreground">
                <i data-lucide="piggy-bank" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">إجمالي طلبات الحسابات الخاصة</p>
                <p class="text-lg font-extrabold text-gold-foreground">{{ number_format($specialRequests) }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-gold/15 text-gold-foreground">
                <i data-lucide="circle-dollar-sign" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">قيمة إجمالي طلبات الحسابات الخاصة</p>
                <p class="text-lg font-extrabold text-gold-foreground">{{ number_format($specialRequestsAmount, 2) }}
                    ج.م
                </p>
            </div>
        </div>
    </div>

    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand/10 text-primary">
                <i data-lucide="receipt" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">إجمالي أذون التسليم المسعّرة</p>
                <p class="text-lg font-extrabold text-foreground">{{ number_format($totalPermits) }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-gold/15 text-gold-foreground">
                <i data-lucide="hand-coins" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">إجمالي قيم الأذون</p>
                <p class="text-lg font-extrabold text-foreground">{{ number_format($totalPermitsAmount, 2) }} ج.م</p>
            </div>
        </div>

        <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-soft">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-success/15 text-success">
                <i data-lucide="badge-check" class="h-5 w-5"></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold text-muted-foreground">أذون تم صرفها</p>
                <p class="text-lg font-extrabold text-foreground">{{ number_format($donePermits) }}</p>
            </div>
        </div>
    </div>

    <div class="mb-5 flex flex-wrap items-center gap-3 rounded-xl border border-border bg-card p-5 shadow-soft">
        <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand/10 text-primary">
            <i data-lucide="layout-dashboard" class="h-5 w-5"></i>
        </span>
        <div class="min-w-0 flex-1">
            <h2 class="text-sm font-bold text-foreground">مركز العمل السريع</h2>
            <p class="text-xs text-muted-foreground">نفّذ المهام الشائعة بنقرة واحدة</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('supply-requests.index') }}"
                class="inline-flex h-9 items-center gap-2 rounded-lg bg-brand px-4 text-[13px] font-bold text-primary-foreground shadow-soft transition-transform hover:-translate-y-0.5">
                <i data-lucide="package-plus" class="h-4 w-4"></i>
                إضافة طلب إمداد
            </a>
            <a href="{{ route('priced-delivery-permits.index') }}"
                class="inline-flex h-9 items-center gap-2 rounded-lg border border-border bg-card px-4 text-[13px] font-bold text-muted-foreground transition-colors hover:bg-accent">
                <i data-lucide="file-plus" class="h-4 w-4"></i>
                إضافة إذن تسليم
            </a>
        </div>
    </div>
@endsection