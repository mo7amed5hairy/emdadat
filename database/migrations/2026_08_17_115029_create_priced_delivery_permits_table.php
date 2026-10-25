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
        Schema::create('priced_delivery_permits', function (Blueprint $table) {
            $table->id();
            $table->decimal('delivery_amount', 15, 2);
            $table->string('supply_request_number'); // رقم الإمداد المرتبط
            $table->string('delivery_permit_number')->unique();
            $table->date('issue_date'); // تاريخ إصدار الإذن
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('form_date'); // تاريخ تحرير الاستمارة/الصرف
            $table->string('status'); // تم الصرف | لم يتم الصرف
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('priced_delivery_permits');
    }
};