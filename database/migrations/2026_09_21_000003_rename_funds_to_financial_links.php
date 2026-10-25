<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('funds', 'financial_links');

        Schema::create('financial_link_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_link_id')->constrained('financial_links')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_link_types');
        Schema::rename('financial_links', 'funds');
    }

    /**
     * زرع الارتباطات المالية الأساسية وأنواعها إن لم تكن موجودة.
     */
    private function seedDefaults(): void
    {
        $budgetTypes = ['مستلزمات طبية', 'خامات أدوية', 'خامات أخرى', 'دائنين', 'تبرعات'];
        $specialTypes = [
            'أشعة', 'أشعة تحديث جهاز الرنين', 'أطفال', 'أطفال الشفة الأرنبية', 'أكاديمية القلب',
            'الخدمات', 'السكتة الدماغية', 'السموم', 'الطب النفسي', 'العبور', 'العيادات الخارجية',
            'الكوبلت', 'الكوبلت صحة المرأة', 'الكوبلت مبادرة الكبد', 'إنزيمات وراثية', 'دمرداش',
            'دمرداش ليزر', 'رعاية استقبال طوارئ', 'شؤون الأطباء', 'صيدلية ثانى', 'عين شمس',
            'عين شمس رعاية صدر', 'عين شمس روماتيزم', 'عين شمس كلى صناعي', 'مسنين', 'مكافحة العدوى',
            'مكافحة العدوى الوحدة المركزية', 'مناظير الجهاز الهضمي', 'نساء', 'نساء إخصاب معملي',
            'نساء تشخيص مبكر أورام مناظير', 'نساء مستلزمات', 'نساء موجات ديناميكية رعاية جنين', 'وراثة أطفال',
        ];

        $now = now();

        $budgetId = DB::table('financial_links')->where('name', 'ميزانية')->value('id');
        if (! $budgetId) {
            $budgetId = DB::table('financial_links')->insertGetId([
                'name' => 'ميزانية',
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $specialId = DB::table('financial_links')->where('name', 'حسابات خاصة')->value('id');
        if (! $specialId) {
            $specialId = DB::table('financial_links')->insertGetId([
                'name' => 'حسابات خاصة',
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $existingBudget = DB::table('financial_link_types')->where('financial_link_id', $budgetId)->pluck('name')->all();
        $existingSpecial = DB::table('financial_link_types')->where('financial_link_id', $specialId)->pluck('name')->all();

        foreach (array_diff($budgetTypes, $existingBudget) as $type) {
            DB::table('financial_link_types')->insert([
                'financial_link_id' => $budgetId,
                'name' => $type,
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (array_diff($specialTypes, $existingSpecial) as $type) {
            DB::table('financial_link_types')->insert([
                'financial_link_id' => $specialId,
                'name' => $type,
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};