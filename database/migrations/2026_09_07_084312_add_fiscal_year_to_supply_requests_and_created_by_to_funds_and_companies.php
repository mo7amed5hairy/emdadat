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
            $table->string('fiscal_year')->nullable()->after('request_date');
        });

        Schema::table('funds', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('name')->constrained('users')->nullOnDelete();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('name');
        });

        // ربط created_by في الشركات بجدول المستخدمين مع الحذف اللين
        Schema::table('companies', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });

        Schema::table('funds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });

        Schema::table('supply_requests', function (Blueprint $table) {
            $table->dropColumn('fiscal_year');
        });
    }
};
