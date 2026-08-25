<?php

declare(strict_types=1);

use App\Actions\Trainings\RequestTrainingAction;
use App\Enums\TrainingRequestStatus;
use App\Exceptions\AlreadyEnrolledException;
use App\Exceptions\AlreadyRequestedException;
use App\Exceptions\EmployeeRetiredException;
use App\Models\Employee;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use App\Models\TrainingRequest;
use Illuminate\Support\Facades\DB;

it('研修受講を承認待ちの申請として作成する', function () {
    $employee = Employee::factory()->create();
    $training = Training::factory()->create();

    $request = (new RequestTrainingAction)->execute($employee, $employee, $training, '業務に必要なため');

    expect($request->employee_id)->toBe($employee->id)
        ->and($request->requested_by_employee_id)->toBe($employee->id)
        ->and($request->training_id)->toBe($training->id)
        ->and($request->status)->toBe(TrainingRequestStatus::Pending)
        ->and($request->reason)->toBe('業務に必要なため');
});

it('上司は部下の分を代理申請でき、申請者として上司自身が記録される', function () {
    $manager = Employee::factory()->create();
    $subordinate = Employee::factory()->create();
    $training = Training::factory()->create();

    $request = (new RequestTrainingAction)->execute($subordinate, $manager, $training);

    expect($request->employee_id)->toBe($subordinate->id)
        ->and($request->requested_by_employee_id)->toBe($manager->id)
        ->and($request->isSelfRequested())->toBeFalse();
});

it('退職済みの従業員は研修を申請できない', function () {
    $employee = Employee::factory()->create(['retired_at' => now()->subDay()]);
    $training = Training::factory()->create();

    expect(fn () => (new RequestTrainingAction)->execute($employee, $employee, $training))
        ->toThrow(EmployeeRetiredException::class);
});

it('既に受講登録されている研修は申請できない', function () {
    $employee = Employee::factory()->create();
    $training = Training::factory()->create();
    TrainingEnrollment::factory()->create(['employee_id' => $employee->id, 'training_id' => $training->id]);

    expect(fn () => (new RequestTrainingAction)->execute($employee, $employee, $training))
        ->toThrow(AlreadyEnrolledException::class);
});

it('同じ研修に承認待ちの申請が既にある場合は申請できない', function () {
    $employee = Employee::factory()->create();
    $training = Training::factory()->create();

    $action = new RequestTrainingAction;
    $action->execute($employee, $employee, $training);

    expect(fn () => $action->execute($employee, $employee, $training))
        ->toThrow(AlreadyRequestedException::class);
});

it('却下済みの申請がある研修は、あらためて申請できる', function () {
    $employee = Employee::factory()->create();
    $training = Training::factory()->create();
    TrainingRequest::factory()->rejected()->create(['employee_id' => $employee->id, 'training_id' => $training->id]);

    $request = (new RequestTrainingAction)->execute($employee, $employee, $training);

    expect($request->status)->toBe(TrainingRequestStatus::Pending);
});

it('本物のデータベース競合状態が発生した場合もAlreadyRequestedExceptionに変換される', function () {
    $employee = Employee::factory()->create();
    $training = Training::factory()->create();

    // exists()チェックを通過した直後（Eloquentのcreatingイベント発火時点）に、
    // 別プロセスが同じ組み合わせで先に申請を完了させてしまった状況を単一コネクション内で再現する。
    TrainingRequest::creating(function () use ($employee, $training): void {
        DB::table('training_requests')->insert([
            'employee_id' => $employee->id,
            'requested_by_employee_id' => $employee->id,
            'training_id' => $training->id,
            'status' => TrainingRequestStatus::Pending->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    try {
        expect(fn () => (new RequestTrainingAction)->execute($employee, $employee, $training))
            ->toThrow(AlreadyRequestedException::class);
    } finally {
        TrainingRequest::flushEventListeners();
    }
});
