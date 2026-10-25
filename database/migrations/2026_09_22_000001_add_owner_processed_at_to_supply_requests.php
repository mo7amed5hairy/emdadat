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
        Schema::table('supply_requests', function (Blueprint $table) {
            // آخر تعامل للمالك المُسند إليه الطلب (يدخل ويُكمل/يعدّل ويحفظ) — يُلغي التمييز اللوني للصف.
            $table->timestamp('owner_processed_at')->nullable()->after('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supply_requests', function (Blueprint $table) {
            $table->dropColumn('owner_processed_at');
        });
    }
};
