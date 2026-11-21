<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * خطط الحفظ/المراجعة البعيدة المتعددة: كل خطة (اسم وتاريخ بداية) تملك أيامها في review_plans.
     * الأيام الموجودة سابقًا تُجمع في خطة واحدة لكل (طالب، نوع) كي لا يضيع شيء.
     */
    public function up(): void
    {
        Schema::create('study_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10); // review | new
            $table->string('title')->nullable();
            $table->date('start_date')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'type']);
        });

        Schema::table('review_plans', function (Blueprint $table) {
            $table->foreignId('study_plan_id')->nullable()->after('student_id')
                ->constrained('study_plans')->cascadeOnDelete();
        });

        $groups = DB::table('review_plans')
            ->select('student_id', 'type', DB::raw('MIN(date) as start_date'), DB::raw('MIN(created_at) as created_at'))
            ->whereNull('study_plan_id')
            ->groupBy('student_id', 'type')
            ->get();

        foreach ($groups as $group) {
            $planId = DB::table('study_plans')->insertGetId([
                'student_id' => $group->student_id,
                'type' => $group->type ?? 'review',
                'title' => null,
                'start_date' => $group->start_date,
                'created_at' => $group->created_at ?? now(),
                'updated_at' => now(),
            ]);

            DB::table('review_plans')
                ->where('student_id', $group->student_id)
                ->where('type', $group->type)
                ->whereNull('study_plan_id')
                ->update(['study_plan_id' => $planId]);
        }
    }

    public function down(): void
    {
        Schema::table('review_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('study_plan_id');
        });
        Schema::dropIfExists('study_plans');
    }
};
