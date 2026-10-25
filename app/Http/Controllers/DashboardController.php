<?php

namespace App\Http\Controllers;

use App\Models\PricedDeliveryPermit;
use App\Models\SupplyRequest;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $totalRequests = SupplyRequest::visibleTo($user)->count();
        $totalRequestsAmount = SupplyRequest::visibleTo($user)->sum('amount');

        $totalPermits = PricedDeliveryPermit::visibleTo($user)->count();
        $totalPermitsAmount = PricedDeliveryPermit::visibleTo($user)->sum('delivery_amount');

        $budgetRequests = SupplyRequest::visibleTo($user)->where('financial_link', 'ميزانية')->count();
        $budgetRequestsAmount = SupplyRequest::visibleTo($user)->where('financial_link', 'ميزانية')->sum('amount');
        $specialRequests = SupplyRequest::visibleTo($user)->where('financial_link', 'حسابات خاصة')->count();
        $specialRequestsAmount = SupplyRequest::visibleTo($user)->where('financial_link', 'حسابات خاصة')->sum('amount');
        $donePermits = PricedDeliveryPermit::visibleTo($user)->where('status', 'تم الصرف')->count();

        return view('dashboard', compact(
            'totalRequests',
            'totalRequestsAmount',
            'totalPermits',
            'totalPermitsAmount',
            'budgetRequests',
            'budgetRequestsAmount',
            'specialRequests',
            'specialRequestsAmount',
            'donePermits',
        ));
    }
}