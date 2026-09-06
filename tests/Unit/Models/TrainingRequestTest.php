<?php

declare(strict_types=1);

use App\Enums\TrainingRequestApprovalStageStatus;
use App\Models\Employee;
use App\Models\TrainingEnrollment;
use App\Models\TrainingRequest;
use App\Models\TrainingRequestApprovalStage;

it('承認によって作られた受講記録を参照できる', function () {
    $enrollment = TrainingEnrollment::factory()->create();
    $request = TrainingRequest::factory()->approved()->create(['training_enrollment_id' => $enrollment->id]);

    expect($request->trainingEnrollment->is($enrollment))->toBeTrue();
});

it('承認前は受講記録が紐付いていない', function () {
    $request = TrainingRequest::factory()->create();

    expect($request->trainingEnrollment)->toBeNull();
});

it('段階番号順に決裁記録一覧を取得できる', function () {
    $trainingRequest = TrainingRequest::factory()->multistage(2)->create();
    TrainingRequestApprovalStage::factory()->create([
        'training_request_id' => $trainingRequest->id,
        'stage_number' => 2,
    ]);
    TrainingRequestApprovalStage::factory()->create([
        'training_request_id' => $trainingRequest->id,
        'stage_number' => 1,
    ]);

    expect($trainingRequest->approvalHistory()->pluck('stage_number')->all())->toBe([1, 2]);
});

it('1段階目が保留中の場合、決裁対象は申請対象の本人になる', function () {
    $employee = Employee::factory()->create();
    $trainingRequest = TrainingRequest::factory()->multistage(2)->create(['employee_id' => $employee->id]);

    expect($trainingRequest->currentStageApprovalTarget()->is($employee))->toBeTrue();
});

it('2段階目以降が保留中の場合、決裁対象は直前の段階を決裁した人になる', function () {
    $stage1Decider = Employee::factory()->create();
    $trainingRequest = TrainingRequest::factory()->multistage(2)->create(['current_approval_stage' => 2]);
    TrainingRequestApprovalStage::factory()->create([
        'training_request_id' => $trainingRequest->id,
        'stage_number' => 1,
        'decided_by_employee_id' => $stage1Decider->id,
        'status' => TrainingRequestApprovalStageStatus::Approved,
    ]);

    expect($trainingRequest->currentStageApprovalTarget()->is($stage1Decider))->toBeTrue();
});
