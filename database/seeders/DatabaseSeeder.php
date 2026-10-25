<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\FinancialLink;
use App\Models\FinancialLinkType;
use App\Models\Hospital;
use App\Models\PricedDeliveryPermit;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'مسؤول النظام',
            'username' => 'admin',
            'password' => '123456',
        ]);

        $hospitals = collect([
            'مستشفى الأمل التخصصي',
            'مستشفى النيل العام',
            'مستشفى الشفاء المركزي',
            'مستشفى الحياة الجامعي',
        ])->map(fn ($name) => Hospital::create(['name' => $name]));

        $departmentMap = [
            'مستشفى الأمل التخصصي' => ['الصيدلية المركزية', 'العناية المركزة', 'الجراحة'],
            'مستشفى النيل العام' => ['المخازن الطبية', 'المعامل', 'الجراحة'],
            'مستشفى الشفاء المركزي' => ['الصيدلية المركزية', 'المعامل', 'العناية المركزة'],
            'مستشفى الحياة الجامعي' => ['المخازن الطبية', 'العناية المركزة', 'المعامل'],
        ];

        $departments = collect();
        foreach ($hospitals as $hospital) {
            foreach ($departmentMap[$hospital->name] as $deptName) {
                $departments->push(Department::create([
                    'hospital_id' => $hospital->id,
                    'name' => $deptName,
                ]));
            }
        }

        $companies = collect([
            'شركة النيل للأدوية',
            'الشركة العربية للمستلزمات الطبية',
            'شركة الدلتا للكيماويات',
            'شركة المستقبل للتجهيزات',
            'الشركة المصرية للتوريدات',
            'شركة الحياة الطبية',
        ])->map(fn ($name) => Company::create(['name' => $name]));

$requestTypes = ['أدوية', 'مستلزمات', 'كيماويات'];
        $financialLinks = ['ميزانية', 'حسابات خاصة'];
        $budgetCategories = \App\Models\SupplyRequest::BUDGET_CATEGORIES;
        $specialAccounts = \App\Models\SupplyRequest::SPECIAL_ACCOUNTS;
        $fundNames = ['الخدمات الطبية', 'التأمين الصحي', 'العلاج الحر', 'المنح'];

        $financialLinkModels = collect($fundNames)->map(fn ($name) => FinancialLink::create(['name' => 'حصيلة ' . $name]));
        $budgetLink = FinancialLink::firstOrCreate(['name' => 'ميزانية']);
        $specialLink = FinancialLink::firstOrCreate(['name' => 'حسابات خاصة']);

        $budgetLinkTypes = collect($budgetCategories)->each(fn ($name) => FinancialLinkType::firstOrCreate([
            'financial_link_id' => $budgetLink->id,
            'name' => $name,
        ]));
        $specialLinkTypes = collect($specialAccounts)->each(fn ($name) => FinancialLinkType::firstOrCreate([
            'financial_link_id' => $specialLink->id,
            'name' => $name,
        ]));

        $supplyRequests = collect();

        for ($i = 0; $i < 23; $i++) {
            $hospital = $hospitals[$i % $hospitals->count()];
            $department = $departments->where('hospital_id', $hospital->id)->first();
            $month = ($i % 12) + 1;

            $financialLink = $financialLinks[$i % 2];
            $financialCategory = match ($financialLink) {
                'ميزانية' => $budgetCategories[$i % count($budgetCategories)],
                default => $specialAccounts[$i % count($specialAccounts)],
            };

            $supplyRequest = SupplyRequest::create([
                'request_type' => $requestTypes[$i % 3],
                'supply_request_number' => 'SR-2026-' . (1001 + $i),
                'hospital_id' => $hospital->id,
                'department_id' => $department->id,
                'financial_link' => $financialLink,
                'financial_category' => $financialCategory,
                'amount' => 45000 + $i * 8350,
                'request_date' => sprintf('2026-%02d-%02d', $month, ($i % 27) + 1),
            ]);

            $supplyRequests->push($supplyRequest);
        }

        $noteStatuses = ['تم الصرف', 'لم يتم الصرف'];

        for ($i = 0; $i < 19; $i++) {
            $month = ($i % 12) + 1;

            PricedDeliveryPermit::create([
                'delivery_amount' => 32000 + $i * 6120,
                'supply_request_number' => $supplyRequests[$i]->supply_request_number,
                'delivery_permit_number' => 'DN-2026-' . (500 + $i),
                'issue_date' => sprintf('2026-%02d-%02d', $month, ($i % 26) + 2),
                'company_id' => $companies[$i % $companies->count()]->id,
                'form_date' => sprintf('2026-%02d-%02d', $month, ($i % 24) + 4),
                'status' => $noteStatuses[$i % 2],
            ]);
        }
    }
}