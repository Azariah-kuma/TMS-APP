<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\TrainingRequest;
use App\Models\TrainingRequestApprovalStage;

it('紐づく申請を取得できる', function () {
    $trainingRequest = TrainingRequest::factory()->create();
    $stage = TrainingRequestApprovalStage::factory()->create(['training_request_id' => $trainingRequest->id]);

    expect($stage->trainingRequest->is($trainingRequest))->toBeTrue();
});

it('決裁した従業員を取得できる', function () {
    $decider = Employee::factory()->create();
    $stage = TrainingRequestApprovalStage::factory()->create(['decided_by_employee_id' => $decider->id]);

    expect($stage->decidedBy->is($decider))->toBeTrue();
});
