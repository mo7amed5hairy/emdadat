<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'نظام طلبات الإمداد') | نظام طلبات الإمداد</title>
    <meta name="description" content="@yield('description', 'نظام طلبات الإمداد وأذون التسليم المسعّرة')" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-foreground antialiased">
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden p-4" style="background-color: #6eced854">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute -right-24 -top-24 h-80 w-80 rounded-full bg-primary/10 blur-3xl"></div>
            <div class="absolute -bottom-32 -left-24 h-96 w-96 rounded-full bg-accent/70 blur-3xl"></div>
            <div class="absolute left-1/4 top-1/3 h-40 w-40 rounded-full bg-gold/10 blur-2xl"></div>
        </div>

        <div class="relative w-full max-w-md">
            <div class="mb-7 flex flex-col items-center gap-3 text-center bg-white p-2 rounded-2xl">
                <span class="grid h-14 w-14 place-items-center rounded-2xl bg-brand text-primary-foreground shadow-glow">
                    <i data-lucide="shield-check" class="h-7 w-7"></i>
                </span>
                <div>
                    <h1 class="text-xl font-extrabold" style="color: #000 !important">نظام طلبات الإمداد</h1>
                    <p class="mt-0.5 text-[12px]" style="color: #000 !important">إدارة التوريدات - مستشفيات جامعة عين شمس</p>
                </div>
            </div>

            @yield('card')
        </div>
    </div>
</body>
</html>