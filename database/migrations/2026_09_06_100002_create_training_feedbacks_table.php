<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 受講完了後に本人が提出する、研修効果測定（アンケート＋簡易テスト）の記録。
 * 1受講記録につき1件（1:1）。ROI算出（BuildTrainingRoiReportAction）の元データになる。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_enrollment_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('satisfaction_score');
            $table->unsignedTinyInteger('understanding_score');
            $table->unsignedTinyInteger('quiz_score')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_feedbacks');
    }
};
