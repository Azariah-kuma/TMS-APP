<?php

declare(strict_types=1);

use App\Actions\Trainings\RejectTrainingRequestAction;
use App\Enums\TrainingRequestStatus;
use App\Exceptions\TrainingRequestNotPendingException;
use App\Models\Employee;
use App\Models\TrainingRequest;

it('申請を却下し、却下理由を記録する', function () {
    $request = TrainingRequest::factory()->create();
    $manager = Employee::factory()->create();

    $rejected = (new RejectTrainingRequestAction)->execute($request, $manager, '予算の都合により見送り');

    expect($rejected->status)->toBe(TrainingRequestStatus::Rejected)
        ->and($rejected->decided_by_employee_id)->toBe($manager->id)
        ->and($rejected->decided_at)->not->toBeNull()
        ->and($rejected->decision_comment)->toBe('予算の都合により見送り');
});

it('却下理由は任意で、指定しなくても却下できる', function () {
    $request = TrainingRequest::factory()->create();

    $rejected = (new RejectTrainingRequestAction)->execute($request, Employee::factory()->create());

    expect($rejected->decision_comment)->toBeNull();
});

it('承認待ちでない申請は却下できない', function () {
    $request = TrainingRequest::factory()->cancelled()->create();

    expect(fn () => (new RejectTrainingRequestAction)->execute($request, Employee::factory()->create()))
        ->toThrow(TrainingRequestNotPendingException::class);
});
