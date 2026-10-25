<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supply_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_type'); // أدوية | مستلزمات | كيماويات
            $table->string('supply_request_number')->unique();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('financial_link'); // ميزانية | حسابات خاصة
            $table->string('financial_category'); // مستلزمات | خامات أدوية | خامات أخرى
            $table->string('fund_name'); // اسم الحصيلة
            $table->decimal('amount', 15, 2);
            $table->date('request_date'); // تاريخ ورود الطلب
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supply_requests');
    }
};