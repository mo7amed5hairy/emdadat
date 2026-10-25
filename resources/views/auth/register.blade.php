@extends('layouts.auth')

@section('title', 'تسجيل مستخدم جديد')
@section('description', 'أنشئ حسابك للوصول إلى نظام طلبات الإمداد وأذون التسليم المسعّرة.')

@section('card')
    <div class="animate-slide-up rounded-2xl border border-border bg-card p-6 shadow-card sm:p-8">
        <div class="mb-6 text-center">
            <h2 class="text-lg font-bold text-foreground">تسجيل مستخدم جديد</h2>
            <p class="mt-1 text-[12px] text-muted-foreground">أدخل بياناتك لإنشاء حساب جديد في النظام</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 flex items-center gap-2 rounded-lg border border-destructive/30 bg-destructive/10 px-3 py-2.5 text-[12px] font-semibold text-destructive">
                <i data-lucide="circle-alert" class="h-4 w-4 shrink-0"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
            @csrf
            <div class="space-y-1.5">
                <label for="name" class="block text-[13px] font-semibold text-foreground/85">الاسم الكامل</label>
                <div class="relative">
                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                        <i data-lucide="user-round" class="h-4 w-4"></i>
                    </span>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="مثال: أحمد محمد سالم" autocomplete="name"
                        class="h-11 w-full rounded-xl border border-input bg-card pe-10 ps-10 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                </div>
            </div>

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
                    <input id="password" name="password" type="password" placeholder="6 أحرف على الأقل" autocomplete="new-password"
                        class="h-11 w-full rounded-xl border border-input bg-card pe-10 ps-10 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                    <button type="button" data-pw-toggle="password" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground transition-colors hover:text-primary" aria-label="إظهار كلمة المرور">
                        <i data-lucide="eye" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            <div class="space-y-1.5">
                <label for="password_confirmation" class="block text-[13px] font-semibold text-foreground/85">تأكيد كلمة المرور</label>
                <div class="relative">
                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                        <i data-lucide="shield-plus" class="h-4 w-4"></i>
                    </span>
                    <input id="password_confirmation" name="password_confirmation" type="password" placeholder="أعد كتابة كلمة المرور" autocomplete="new-password"
                        class="h-11 w-full rounded-xl border border-input bg-card pe-10 ps-10 text-[13px] text-foreground outline-none transition-shadow placeholder:text-muted-foreground/70 focus:border-primary focus:ring-2 focus:ring-ring/35" />
                </div>
            </div>

            <label class="flex cursor-pointer items-start gap-2 text-xs text-muted-foreground">
                <input type="checkbox" name="terms" class="mt-0.5 h-4 w-4 rounded accent-primary" />
                أوافق على الشروط والأحكام الخاصة باستخدام النظام وفقاً للسياسات المعتمدة.
            </label>

            <button type="submit"
                class="mt-1 flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-brand text-sm font-bold text-primary-foreground shadow-glow transition-transform hover:-translate-y-0.5">
                <i data-lucide="user-plus" class="h-4 w-4"></i>
                إنشاء الحساب
            </button>
        </form>

        <div class="mt-4 flex items-center gap-2 rounded-xl border border-border bg-muted/60 px-3 py-2.5 text-[11px] text-muted-foreground">
            <i data-lucide="shield-plus" class="h-4 w-4 shrink-0 text-primary"></i>
            لن يتم استخدام بيانات الحساب إلا لأغراض النظام فقط، وبياناتك محمية.
        </div>

        <p class="mt-5 text-center text-xs text-muted-foreground">
            لديك حساب بالفعل؟
            <a href="{{ route('login') }}" class="font-bold text-primary transition-colors hover:underline">تسجيل الدخول</a>
        </p>
    </div>
@endsection