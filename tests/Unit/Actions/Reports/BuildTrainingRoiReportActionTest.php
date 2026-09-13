<?php

declare(strict_types=1);

use App\Actions\Reports\BuildTrainingRoiReportAction;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use App\Models\TrainingFeedback;

it('フィードバックの平均スコアと単価から効果スコア・ROI指標を算出する', function () {
    $training = Training::factory()->create(['unit_cost' => 10000]);

    $enrollmentA = TrainingEnrollment::factory()->create(['training_id' => $training->id]);
    TrainingFeedback::factory()->create([
        'training_enrollment_id' => $enrollmentA->id,
        'satisfaction_score' => 5,
        'understanding_score' => 5,
        'quiz_score' => 100,
    ]);

    $enrollmentB = TrainingEnrollment::factory()->create(['training_id' => $training->id]);
    TrainingFeedback::factory()->create([
        'training_enrollment_id' => $enrollmentB->id,
        'satisfaction_score' => 3,
        'understanding_score' => 3,
        'quiz_score' => 60,
    ]);

    $report = (new BuildTrainingRoiReportAction)->execute();
    $row = collect($report)->firstWhere('training_id', $training->id);

    // 満足度平均4/理解度平均4 → 100点換算80点、テスト平均80点 → 効果スコア(80+80+80)/3=80
    expect($row['feedback_count'])->toBe(2)
        ->and($row['avg_satisfaction_score'])->toBe(4.0)
        ->and($row['avg_understanding_score'])->toBe(4.0)
        ->and($row['avg_quiz_score'])->toBe(80.0)
        ->and($row['effectiveness_score'])->toBe(80.0)
        ->and($row['roi_index'])->toBe(0.008);
});

it('テスト得点が1件も提出されていない場合はアンケートのみで効果スコアを算出する', function () {
    $training = Training::factory()->create(['unit_cost' => 5000]);
    $enrollment = TrainingEnrollment::factory()->create(['training_id' => $training->id]);
    TrainingFeedback::factory()->create([
        'training_enrollment_id' => $enrollment->id,
        'satisfaction_score' => 5,
        'understanding_score' => 5,
        'quiz_score' => null,
    ]);

    $report = (new BuildTrainingRoiReportAction)->execute();
    $row = collect($report)->firstWhere('training_id', $training->id);

    expect($row['avg_quiz_score'])->toBeNull()
        ->and($row['effectiveness_score'])->toBe(100.0);
});

it('単価未設定の研修はroi_indexがnullになる', function () {
    $training = Training::factory()->create(['unit_cost' => null]);
    $enrollment = TrainingEnrollment::factory()->create(['training_id' => $training->id]);
    TrainingFeedback::factory()->create(['training_enrollment_id' => $enrollment->id]);

    $report = (new BuildTrainingRoiReportAction)->execute();
    $row = collect($report)->firstWhere('training_id', $training->id);

    expect($row['unit_cost'])->toBeNull()
        ->and($row['roi_index'])->toBeNull();
});

it('フィードバックが1件も無い研修はレポートに含まれない', function () {
    $training = Training::factory()->create(['unit_cost' => 1000]);

    $report = (new BuildTrainingRoiReportAction)->execute();

    expect(collect($report)->firstWhere('training_id', $training->id))->toBeNull();
});
