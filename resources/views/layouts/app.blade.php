<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'لوحة التحكم') | نظام طلبات الإمداد</title>
    <meta name="description" content="@yield('description', 'نظام طلبات الإمداد وأذون التسليم المسعّرة')" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-foreground antialiased">
    <div class="flex min-h-screen bg-background">

        @php
            $navItems = [
                ['route' => 'dashboard', 'label' => 'لوحة التحكم', 'icon' => 'layout-dashboard', 'href' => route('dashboard')],
                ['route' => 'supply-requests.index', 'label' => 'طلبات الإمداد', 'icon' => 'package-search', 'href' => route('supply-requests.index'), 'screen' => 'supply_requests'],
                ['route' => 'priced-delivery-permits.index', 'label' => 'أذون تسليم مسعّر', 'icon' => 'receipt', 'href' => route('priced-delivery-permits.index'), 'screen' => 'priced_delivery_permits'],
                ['route' => 'reports.supply-requests', 'label' => 'تقارير طلبات الإمداد', 'icon' => 'file-chart-column', 'href' => route('reports.supply-requests')],
                ['route' => 'reports.priced-delivery-permits', 'label' => 'تقارير أذون التسليم', 'icon' => 'clipboard-list', 'href' => route('reports.priced-delivery-permits')],
                ['route' => 'reports.annual-un-disbursed', 'label' => 'تقرير سنوى لما لم يتم صرفه', 'icon' => 'calendar-x2', 'href' => route('reports.annual-un-disbursed')],
                ['route' => 'hospitals.index', 'label' => 'المستشفيات والأقسام', 'icon' => 'building-2', 'href' => route('hospitals.index')],
                ['route' => 'financial-links.index', 'label' => 'الارتباط المالي', 'icon' => 'link', 'href' => route('financial-links.index')],
                ['route' => 'companies.index', 'label' => 'الشركات', 'icon' => 'factory', 'href' => route('companies.index')],
                ['route' => 'users.index', 'label' => 'إدارة المستخدمين', 'icon' => 'users', 'href' => route('users.index'), 'admin' => true],
            ];
            $allowedScreens = auth()->user()?->permittedScreens();
            $current = request()->route() ? request()->route()->getName() : '';
        @endphp

        <!-- ===== الشريط الجانبي (سطح المكتب) ===== -->
        <aside class="sticky top-0 hidden h-screen w-64 shrink-0 flex-col border-e border-border bg-sidebar text-sidebar-foreground lg:flex">
            <div class="flex items-center gap-3 border-b border-sidebar-border px-5 py-5">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground">
                    <i data-lucide="shield-check" class="h-5 w-5"></i>
                </span>
                <div>
                    <p class="text-sm font-bold leading-tight">نظام طلبات الإمداد</p>
                    <p class="text-[11px] opacity-70">إدارة التوريدات - مستشفيات جامعة عين شمس</p>
                </div>
            </div>

            <nav class="flex-1 space-y-1 px-3 py-4">
                @foreach ($navItems as $item)
                    @if (!empty($item['admin']) && !auth()->user()->is_admin)
                        @continue
                    @endif
                    @if ($allowedScreens !== null && (!isset($item['screen']) || !in_array($item['screen'], $allowedScreens, true)))
                        @continue
                    @endif
                    <a href="{{ $item['href'] }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-[13px] font-medium transition-colors {{ $current === $item['route'] ? 'bg-sidebar-primary text-sidebar-primary-foreground shadow-soft' : 'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground' }}">
                        <i data-lucide="{{ $item['icon'] }}" class="h-[18px] w-[18px]"></i>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-sidebar-border p-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-[13px] font-medium transition-colors hover:bg-sidebar-accent">
                        <i data-lucide="log-out" class="h-[18px] w-[18px]"></i>
                        تسجيل الخروج
                    </button>
                </form>
            </div>
        </aside>

        <!-- ===== درج الموبايل ===== -->
        <div id="mobile-overlay" class="fixed inset-0 z-40 hidden bg-foreground/45 backdrop-blur-sm lg:hidden"></div>
        <aside id="mobile-drawer" class="fixed inset-y-0 right-0 z-50 hidden w-64 flex-col bg-sidebar text-sidebar-foreground shadow-card lg:hidden">
            <div class="flex items-center justify-between border-b border-sidebar-border px-5 py-4">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground">
                        <i data-lucide="shield-check" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <p class="text-sm font-bold leading-tight">نظام طلبات الإمداد</p>
                        <p class="text-[11px] opacity-70">إدارة التوريدات - مستشفيات جامعة عين شمس</p>
                    </div>
                </div>
                <button type="button" id="drawer-close" class="grid h-9 w-9 place-items-center rounded-lg transition-colors hover:bg-sidebar-accent" aria-label="إغلاق القائمة">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
            <nav class="flex-1 space-y-1 px-3 py-4">
                @foreach ($navItems as $item)
                    @if (!empty($item['admin']) && !auth()->user()->is_admin)
                        @continue
                    @endif
                    @if ($allowedScreens !== null && (!isset($item['screen']) || !in_array($item['screen'], $allowedScreens, true)))
                        @continue
                    @endif
                    <a href="{{ $item['href'] }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-[13px] font-medium transition-colors {{ $current === $item['route'] ? 'bg-sidebar-primary text-sidebar-primary-foreground shadow-soft' : 'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground' }}">
                        <i data-lucide="{{ $item['icon'] }}" class="h-[18px] w-[18px]"></i>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
            <div class="border-t border-sidebar-border p-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-[13px] font-medium transition-colors hover:bg-sidebar-accent">
                        <i data-lucide="log-out" class="h-[18px] w-[18px]"></i>
                        تسجيل الخروج
                    </button>
                </form>
            </div>
        </aside>

        <!-- ===== المحتوى الرئيسي ===== -->
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 border-b border-border bg-card/85 backdrop-blur">
                <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button" id="menu-btn" class="grid h-9 w-9 place-items-center rounded-lg border border-border bg-card text-muted-foreground transition-colors hover:bg-accent lg:hidden" aria-label="فتح القائمة">
                            <i data-lucide="menu" class="h-[18px] w-[18px]"></i>
                        </button>
                        <div class="min-w-0">
                            <h1 class="truncate text-lg font-bold text-foreground">@yield('page-title')</h1>
                            <p class="truncate text-xs text-muted-foreground">@yield('page-subtitle')</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <!-- ===== الإشعارات ===== -->
                        <div class="relative" id="notif-wrap">
                            <button type="button" id="notif-btn" class="relative grid h-9 w-9 place-items-center rounded-lg border border-border bg-card transition-colors hover:bg-accent" aria-label="الإشعارات">
                                <i data-lucide="bell" class="h-[18px] w-[18px] text-muted-foreground"></i>
                                <span id="notif-dot" class="absolute -left-0.5 -top-0.5 h-2.5 w-2.5 rounded-full bg-gold" style="display:none"></span>
                            </button>
                            <div id="notif-menu" class="absolute left-0 top-full z-30 mt-2 hidden w-72 overflow-hidden rounded-xl border border-border bg-card shadow-card animate-zoom-in">
                                <div class="flex items-center justify-between border-b border-border px-4 py-3">
                                    <p class="text-[13px] font-bold text-foreground">الإشعارات</p>
                                    <span class="rounded-md bg-muted px-2 py-0.5 text-[11px] font-semibold text-muted-foreground" id="notif-count">0</span>
                                </div>
                                <div class="max-h-72 overflow-y-auto p-2" id="notif-list">
                                    <div class="flex flex-col items-center gap-2 px-4 py-8 text-center">
                                        <span class="grid h-11 w-11 place-items-center rounded-full bg-muted text-muted-foreground">
                                            <i data-lucide="bell-off" class="h-5 w-5"></i>
                                        </span>
                                        <p class="text-[12px] text-muted-foreground">لا توجد إشعارات</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ===== الملف الشخصي ===== -->
                        <div class="relative" id="profile-wrap">
                            <button type="button" id="profile-btn" class="flex items-center gap-2 rounded-lg border border-border bg-card py-1.5 pe-2 ps-3 transition-colors hover:bg-accent" aria-label="الملف الشخصي">
                                <div class="text-left">
                                    <p class="text-[12px] font-semibold leading-tight">{{ auth()->user()->name }}</p>
                                    <p class="text-[10px] text-muted-foreground">@ {{ auth()->user()->username }}</p>
                                </div>
                                <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand text-primary-foreground">
                                    <i data-lucide="user-round" class="h-4 w-4"></i>
                                </span>
                                <i data-lucide="chevron-down" class="h-3.5 w-3.5 text-muted-foreground"></i>
                            </button>
                            <div id="profile-menu" class="absolute left-0 top-full z-30 mt-2 hidden w-52 overflow-hidden rounded-xl border border-border bg-card shadow-card animate-zoom-in">
                                <div class="border-b border-border px-4 py-3">
                                    <p class="truncate text-[13px] font-bold text-foreground">{{ auth()->user()->name }}</p>
                                    <p class="truncate text-[11px] text-muted-foreground">{{ auth()->user()->username }}</p>
                                </div>
                                <div class="p-1.5">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] font-medium text-destructive transition-colors hover:bg-destructive/10">
                                            <i data-lucide="log-out" class="h-4 w-4"></i>
                                            تسجيل الخروج
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-5 lg:p-6">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- ===== مودال التأكيد ===== -->
    <div id="confirm-modal" class="fixed inset-0 z-[60] items-center justify-center bg-foreground/45 p-4 backdrop-blur-sm" style="display:none" onclick="window.emdadat.closeOnBackdrop('confirm-modal', event)">
        <div class="animate-zoom-in w-full max-w-md overflow-hidden rounded-2xl border border-border bg-card shadow-card">
            <div class="flex items-center gap-3 border-b border-border px-5 py-4">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-destructive/10 text-destructive">
                    <i data-lucide="triangle-alert" class="h-5 w-5"></i>
                </span>
                <h3 class="text-base font-bold text-foreground">تأكيد الحذف</h3>
            </div>
            <div class="p-5">
                <p id="confirm-text" class="text-[13px] text-muted-foreground"></p>
                <div class="mt-5 flex flex-wrap items-center justify-end gap-2">
                    <button type="button" id="confirm-no"
                        class="inline-flex h-9 items-center rounded-lg border border-border bg-card px-4 text-[13px] font-bold text-muted-foreground transition-colors hover:bg-accent">
                        إلغاء
                    </button>
                    <button type="button" id="confirm-yes"
                        class="inline-flex h-9 items-center gap-2 rounded-lg bg-destructive px-4 text-[13px] font-bold text-destructive-foreground shadow-soft transition-transform hover:-translate-y-0.5">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                        تأكيد الحذف
                    </button>
                </div>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>