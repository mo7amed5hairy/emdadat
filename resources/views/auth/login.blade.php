@extends('layouts.auth')

@section('title', 'تسجيل الدخول')
@section('description', 'بوابة الدخول الآمنة لنظام طلبات الإمداد الموحد وأذون التسليم المسعّرة.')

@section('card')
    <div class="animate-slide-up rounded-2xl border border-border bg-card p-6 shadow-card sm:p-8">
        <div class="mb-6 text-center">
            <h2 class="text-lg font-bold text-foreground">تسجيل الدخول</h2>
            <p class="mt-1 text-[12px] text-muted-foreground">أدخل بيانات حسابك للوصول إلى لوحة إدارة طلبات الإمداد</p>
        </div>

        @if (session('error'))
            <div class="mb-4 flex items-center gap-2 rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2.5 text-[12px] font-semibold text-destructive">
                <i data-lucide="circle-alert" class="h-4 w-4 shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 flex items-center gap-2 rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2.5 text-[12px] font-semibold text-destructive">
                <i data-lucide="circle-alert" class="h-4 w-4 shrink-0"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
            @csrf
            <div class="space-y-1.5">
                <label for="username" class="block text-[13px] font-semibold text-foreground/85">اسم المستخدم</label>
                <div class="relative">
                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                        <i data-lucide="user" class="h-4 w-4"></i>
                    </span>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" placeholder="أدخل اسم المستخدم" autocomplete="username"
                        class="h-11 w-full rounded-xl border border-input bg-card pe-10 ps-10 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                </div>
            </div>

            <div class="space-y-1.5">
                <label for="password" class="block text-[13px] font-semibold text-foreground/85">كلمة المرور</label>
                <div class="relative">
                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                        <i data-lucide="key-round" class="h-4 w-4"></i>
                    </span>
                    <input id="password" name="password" type="password" placeholder="••••••••" autocomplete="current-password"
                        class="h-11 w-full rounded-xl border border-input bg-card pe-10 ps-10 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                    <button type="button" data-pw-toggle="password" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground transition-colors hover:text-primary" aria-label="إظهار كلمة المرور">
                        <i data-lucide="eye" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex cursor-pointer items-center gap-2 text-xs text-muted-foreground">
                    <input type="checkbox" name="remember" class="h-4 w-4 rounded accent-primary" />
                    تذكرني على هذا الجهاز
                </label>
            </div>

            <button type="submit"
                class="mt-2 flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-brand text-sm font-bold text-primary-foreground shadow-glow transition-transform hover:-translate-y-0.5">
                <i data-lucide="log-in" class="h-4 w-4"></i>
                تسجيل الدخول
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-muted-foreground">
            ليس لديك حساب؟
            <a href="{{ route('register') }}" class="font-bold text-primary transition-colors hover:underline">تسجيل مستخدم جديد</a>
        </p>
    </div>
@endsection