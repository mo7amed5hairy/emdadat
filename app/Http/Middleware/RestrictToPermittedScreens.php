<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictToPermittedScreens
{
    /**
     * مسارات AJAX المساعدة التي يحتاجها فورم كل شاشة (تُسمح فقط لمن تملك تلك الشاشة).
     *
     * @var array<string, array<int, string>>
     */
    private const HELPER_ROUTES = [
        'supply-requests.lookup' => ['supply_requests', 'priced_delivery_permits'],
        'hospitals.quick-add' => ['supply_requests'],
        'departments.quick-add' => ['supply_requests'],
        'departments.by-hospital' => ['supply_requests'],
        'companies.quick-add' => ['priced_delivery_permits'],
    ];

    /**
     * بادئة اسم الراوت => الشاشة المالكة له.
     *
     * @var array<string, string>
     */
    private const SCREEN_PREFIXES = [
        'supply-requests' => 'supply_requests',
        'priced-delivery-permits' => 'priced_delivery_permits',
    ];

    /**
     * منع المستخدم ذي الصلاحيات الجزئية من دخول أي صفحة غير شاشات فورماته.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user) {
            return $next($request);
        }

        $allowed = $user->permittedScreens();

        // غير مقيد (مدير أو بدون input_permissions) — يدخل كل الصفحات.
        if ($allowed === null) {
            return $next($request);
        }

        $routeName = (string) ($request->route()?->getName() ?? '');

        if (isset(self::HELPER_ROUTES[$routeName])) {
            if (array_intersect($allowed, self::HELPER_ROUTES[$routeName])) {
                return $next($request);
            }

            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
        }

        foreach (self::SCREEN_PREFIXES as $prefix => $screen) {
            if (str_starts_with($routeName, $prefix)) {
                if (in_array($screen, $allowed, true)) {
                    return $next($request);
                }

                abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
            }
        }

        // لوحة التحكم: تُحوَّل لأول شاشة مسموحة بدل 403 (لضمان دخول سلس بعد تسجيل الدخول).
        if ($routeName === 'dashboard') {
            foreach (self::SCREEN_PREFIXES as $prefix => $screen) {
                if (in_array($screen, $allowed, true)) {
                    return redirect()->route($prefix . '.index');
                }
            }
        }

        abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
    }
}
