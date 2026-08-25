<?php

declare(strict_types=1);

use App\Actions\Trainings\CancelTrainingRequestAction;
use App\Enums\TrainingRequestStatus;
use App\Exceptions\TrainingRequestNotPendingException;
use App\Models\TrainingRequest;

it('承認待ちの申請を取り消せる', function () {
    $request = TrainingRequest::factory()->create();

    $cancelled = (new CancelTrainingRequestAction)->execute($request);

    expect($cancelled->status)->toBe(TrainingRequestStatus::Cancelled)
        ->and($cancelled->decided_by_employee_id)->toBeNull();
});

it('承認待ちでない申請は取り消せない', function () {
    $request = TrainingRequest::factory()->approved()->create();

    expect(fn () => (new CancelTrainingRequestAction)->execute($request))
        ->toThrow(TrainingRequestNotPendingException::class);
});
