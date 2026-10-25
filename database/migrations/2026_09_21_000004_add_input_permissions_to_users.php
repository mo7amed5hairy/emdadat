<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * أذونات إضافة المدخلات لكل مستخدم.
     *
     * values: null (غير مقيد) أو شاشة بها {permitted, fields}.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('input_permissions')->nullable()->after('department_id');
        });

        /* الحقول التي قد يمنعها المسؤول من مستخدم مقيد تصبح قابلة للإخالاء. */
        Schema::table('supply_requests', function (Blueprint $table) {
            $table->string('request_type')->nullable()->change();
            $table->foreignId('hospital_id')->nullable()->change();
            $table->foreignId('department_id')->nullable()->change();
            $table->string('financial_link')->nullable()->change();
            $table->string('financial_category')->nullable()->change();
            $table->decimal('amount', 15, 2)->nullable()->change();
            $table->date('request_date')->nullable()->change();
        });

        Schema::table('priced_delivery_permits', function (Blueprint $table) {
            $table->decimal('delivery_amount', 15, 2)->nullable()->change();
            $table->string('supply_request_number')->nullable()->change();
            $table->string('delivery_permit_number')->nullable()->change();
            $table->date('issue_date')->nullable()->change();
            $table->foreignId('company_id')->nullable()->change();
            $table->date('form_date')->nullable()->change();
            $table->string('status')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('input_permissions');
        });

        Schema::table('supply_requests', function (Blueprint $table) {
            $table->string('request_type')->nullable(false)->change();
            $table->foreignId('hospital_id')->nullable(false)->change();
            $table->foreignId('department_id')->nullable(false)->change();
            $table->string('financial_link')->nullable(false)->change();
            $table->string('financial_category')->nullable(false)->change();
            $table->decimal('amount', 15, 2)->nullable(false)->change();
            $table->date('request_date')->nullable(false)->change();
        });

        Schema::table('priced_delivery_permits', function (Blueprint $table) {
            $table->decimal('delivery_amount', 15, 2)->nullable(false)->change();
            $table->string('supply_request_number')->nullable(false)->change();
            $table->string('delivery_permit_number')->nullable(false)->change();
            $table->date('issue_date')->nullable(false)->change();
            $table->foreignId('company_id')->nullable(false)->change();
            $table->date('form_date')->nullable(false)->change();
            $table->string('status')->nullable(false)->change();
        });
    }
};