<?php

declare(strict_types=1);

use App\Models\TrainingEnrollment;
use App\Models\TrainingFeedback;

it('紐づく受講記録を取得できる', function () {
    $enrollment = TrainingEnrollment::factory()->create();
    $feedback = TrainingFeedback::factory()->create(['training_enrollment_id' => $enrollment->id]);

    expect($feedback->trainingEnrollment->is($enrollment))->toBeTrue();
});

it('受講記録から提出済みのフィードバックを取得できる', function () {
    $enrollment = TrainingEnrollment::factory()->create();
    $feedback = TrainingFeedback::factory()->create(['training_enrollment_id' => $enrollment->id]);

    expect($enrollment->trainingFeedback->is($feedback))->toBeTrue();
});

it('フィードバック未提出の受講記録はnullを返す', function () {
    $enrollment = TrainingEnrollment::factory()->create();

    expect($enrollment->trainingFeedback)->toBeNull();
});
