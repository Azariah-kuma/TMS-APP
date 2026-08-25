<?php

declare(strict_types=1);

use App\Actions\Trainings\BulkApproveTrainingRequestsAction;
use App\Enums\TrainingRequestStatus;
use App\Models\Employee;
use App\Models\TrainingEnrollment;
use App\Models\TrainingRequest;

it('複数の申請をまとめて承認し、受講記録を作成する', function () {
    $hr = Employee::factory()->create();
    $requests = TrainingRequest::factory()->count(3)->create();

    $result = app(BulkApproveTrainingRequestsAction::class)->execute($requests, $hr);

    expect($result)->toBe(['approved' => 3]);

    $requests->each(function (TrainingRequest $request) {
        $fresh = $request->fresh();
        expect($fresh->status)->toBe(TrainingRequestStatus::Approved)
            ->and($fresh->training_enrollment_id)->not->toBeNull();

        expect(TrainingEnrollment::where('employee_id', $fresh->employee_id)
            ->where('training_id', $fresh->training_id)->exists())->toBeTrue();
    });
});

it('承認待ちでなくなっている申請はスキップし、他の申請の承認は続行する', function () {
    $hr = Employee::factory()->create();
    $pending = TrainingRequest::factory()->create();
    $alreadyApproved = TrainingRequest::factory()->approved()->create();

    $result = app(BulkApproveTrainingRequestsAction::class)->execute(
        collect([$pending, $alreadyApproved]),
        $hr,
    );

    expect($result)->toBe(['approved' => 1])
        ->and($pending->fresh()->status)->toBe(TrainingRequestStatus::Approved);
});

it('渡された申請が0件の場合は何もしない', function () {
    $hr = Employee::factory()->create();

    $result = app(BulkApproveTrainingRequestsAction::class)->execute(collect(), $hr);

    expect($result)->toBe(['approved' => 0]);
});
