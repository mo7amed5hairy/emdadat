<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('supply_requests', function (Blueprint $table) {
            $table->foreignId('assigned_to')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('priced_delivery_permits', function (Blueprint $table) {
            $table->foreignId('assigned_to')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
        });

        // الحقول القائمة المملوكة لصاحب الحساب = المُنشئ نفسه.
        DB::table('supply_requests')->whereNull('assigned_to')->update([
            'assigned_to' => DB::raw('created_by'),
        ]);
        DB::table('priced_delivery_permits')->whereNull('assigned_to')->update([
            'assigned_to' => DB::raw('created_by'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('priced_delivery_permits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
        });

        Schema::table('supply_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
        });
    }
};