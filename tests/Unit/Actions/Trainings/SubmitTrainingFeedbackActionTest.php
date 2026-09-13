<?php

declare(strict_types=1);

use App\Actions\Trainings\SubmitTrainingFeedbackAction;
use App\Enums\TrainingEnrollmentStatus;
use App\Exceptions\TrainingEnrollmentNotCompletedException;
use App\Exceptions\TrainingFeedbackAlreadySubmittedException;
use App\Models\TrainingEnrollment;

it('受講完了済みの受講記録にアンケート・テストの結果を提出する', function () {
    $enrollment = TrainingEnrollment::factory()->create(['status' => TrainingEnrollmentStatus::Completed]);

    $feedback = (new SubmitTrainingFeedbackAction)->execute($enrollment, 5, 4, 90, '大変わかりやすかったです');

    expect($feedback->satisfaction_score)->toBe(5)
        ->and($feedback->understanding_score)->toBe(4)
        ->and($feedback->quiz_score)->toBe(90)
        ->and($feedback->comment)->toBe('大変わかりやすかったです')
        ->and($feedback->training_enrollment_id)->toBe($enrollment->id);
});

it('テスト得点・コメントは省略できる', function () {
    $enrollment = TrainingEnrollment::factory()->create(['status' => TrainingEnrollmentStatus::Completed]);

    $feedback = (new SubmitTrainingFeedbackAction)->execute($enrollment, 4, 4, null, null);

    expect($feedback->quiz_score)->toBeNull()
        ->and($feedback->comment)->toBeNull();
});

it('受講が完了していない場合は提出できない', function () {
    $enrollment = TrainingEnrollment::factory()->create(['status' => TrainingEnrollmentStatus::InProgress]);

    expect(fn () => (new SubmitTrainingFeedbackAction)->execute($enrollment, 5, 5, null, null))
        ->toThrow(TrainingEnrollmentNotCompletedException::class);
});

it('既に提出済みの場合は再提出できない', function () {
    $enrollment = TrainingEnrollment::factory()->create(['status' => TrainingEnrollmentStatus::Completed]);
    (new SubmitTrainingFeedbackAction)->execute($enrollment, 5, 5, null, null);

    expect(fn () => (new SubmitTrainingFeedbackAction)->execute($enrollment, 3, 3, null, null))
        ->toThrow(TrainingFeedbackAlreadySubmittedException::class);
});
