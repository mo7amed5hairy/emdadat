<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('priced_delivery_permits', function (Blueprint $table) {
            $table->boolean('fully_delivered')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('priced_delivery_permits', function (Blueprint $table) {
            $table->dropColumn('fully_delivered');
        });
    }
};