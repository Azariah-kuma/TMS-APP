<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 従業員（正確には受講記録=TrainingEnrollment）ごとの、Lesson完了チェック。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_lesson_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_lesson_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->unique(['training_enrollment_id', 'training_lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_lesson_completions');
    }
};
