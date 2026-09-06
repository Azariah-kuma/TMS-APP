<?php

declare(strict_types=1);

use App\Models\Delegation;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\TrainingRequest;
use App\Models\TrainingRequestApprovalStage;
use App\Models\User;

it('従業員なら誰でも申請一覧を要求できる', function () {
    $employee = Employee::factory()->create();

    expect($employee->user->can('viewAny', TrainingRequest::class))->toBeTrue();
});

it('従業員レコードのないユーザーは申請一覧を要求できない', function () {
    $user = User::factory()->create();

    expect($user->can('viewAny', TrainingRequest::class))->toBeFalse();
});

it('人事はどの申請も閲覧できる', function () {
    $hr = Employee::factory()->hr()->create();
    $request = TrainingRequest::factory()->create();

    expect($hr->user->can('view', $request))->toBeTrue();
});

it('本人は自分の申請を閲覧できる', function () {
    $employee = Employee::factory()->create();
    $request = TrainingRequest::factory()->create(['employee_id' => $employee->id]);

    expect($employee->user->can('view', $request))->toBeTrue();
});

it('上司は部下の申請を閲覧できる', function () {
    $manager = Employee::factory()->create();
    $subordinate = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $subordinate->id, 'manager_id' => $manager->id]);
    $request = TrainingRequest::factory()->create(['employee_id' => $subordinate->id]);

    expect($manager->user->can('view', $request))->toBeTrue();
});

it('無関係な従業員は申請を閲覧できない', function () {
    $bystander = Employee::factory()->create();
    $request = TrainingRequest::factory()->create();

    expect($bystander->user->can('view', $request))->toBeFalse();
});

it('従業員レコードのないユーザーは申請を閲覧できない', function () {
    $user = User::factory()->create();
    $request = TrainingRequest::factory()->create();

    expect($user->can('view', $request))->toBeFalse();
});

it('ログイン中の従業員なら誰でも自分の研修受講を申請できる', function () {
    $employee = Employee::factory()->create();

    expect($employee->user->can('create', TrainingRequest::class))->toBeTrue();
});

it('従業員レコードのないユーザーは研修受講を申請できない', function () {
    $user = User::factory()->create();

    expect($user->can('create', TrainingRequest::class))->toBeFalse();
});

it('上司は部下の代理で研修受講を申請できる', function () {
    $manager = Employee::factory()->create();
    $subordinate = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $subordinate->id, 'manager_id' => $manager->id]);

    expect($manager->user->can('create', [TrainingRequest::class, $subordinate]))->toBeTrue();
});

it('無関係な従業員の代理では研修受講を申請できない', function () {
    $bystander = Employee::factory()->create();
    $someone = Employee::factory()->create();

    expect($bystander->user->can('create', [TrainingRequest::class, $someone]))->toBeFalse();
});

it('人事は誰の代理でも研修受講を申請できる', function () {
    $hr = Employee::factory()->hr()->create();
    $someone = Employee::factory()->create();

    expect($hr->user->can('create', [TrainingRequest::class, $someone]))->toBeTrue();
});

it('部下がいる上司は部署一括申請ができる', function () {
    $manager = Employee::factory()->create();
    $subordinate = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $subordinate->id, 'manager_id' => $manager->id]);

    expect($manager->user->can('bulkCreate', TrainingRequest::class))->toBeTrue();
});

it('部下がいない一般社員は部署一括申請ができない', function () {
    $employee = Employee::factory()->create();

    expect($employee->user->can('bulkCreate', TrainingRequest::class))->toBeFalse();
});

it('人事は部署一括申請ができる', function () {
    $hr = Employee::factory()->hr()->create();

    expect($hr->user->can('bulkCreate', TrainingRequest::class))->toBeTrue();
});

it('従業員レコードのないユーザーは部署一括申請ができない', function () {
    $user = User::factory()->create();

    expect($user->can('bulkCreate', TrainingRequest::class))->toBeFalse();
});

it('上司は部下の申請を承認できる', function () {
    $manager = Employee::factory()->create();
    $subordinate = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $subordinate->id, 'manager_id' => $manager->id]);
    $request = TrainingRequest::factory()->create(['employee_id' => $subordinate->id]);

    expect($manager->user->can('approve', $request))->toBeTrue()
        ->and($manager->user->can('reject', $request))->toBeTrue();
});

it('委任を受けている代理上司も、委任元の部下の申請を承認できる', function () {
    $manager = Employee::factory()->create();
    $subordinate = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $subordinate->id, 'manager_id' => $manager->id]);
    $delegate = Employee::factory()->create();
    Delegation::factory()->create([
        'delegator_id' => $manager->id,
        'delegate_id' => $delegate->id,
        'started_at' => now()->subDay(),
        'ended_at' => now()->addDay(),
    ]);
    $request = TrainingRequest::factory()->create(['employee_id' => $subordinate->id]);

    expect($delegate->user->can('approve', $request))->toBeTrue();
});

it('上司による代理申請は、申請元の上司自身も含めて上司側は誰も承認・却下できない', function () {
    $manager = Employee::factory()->create();
    $subordinate = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $subordinate->id, 'manager_id' => $manager->id]);
    $request = TrainingRequest::factory()->create([
        'employee_id' => $subordinate->id,
        'requested_by_employee_id' => $manager->id,
    ]);

    expect($manager->user->can('approve', $request))->toBeFalse()
        ->and($manager->user->can('reject', $request))->toBeFalse();
});

it('上司による代理申請は人事のみ承認・却下できる', function () {
    $hr = Employee::factory()->hr()->create();
    $manager = Employee::factory()->create();
    $subordinate = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $subordinate->id, 'manager_id' => $manager->id]);
    $request = TrainingRequest::factory()->create([
        'employee_id' => $subordinate->id,
        'requested_by_employee_id' => $manager->id,
    ]);

    expect($hr->user->can('approve', $request))->toBeTrue()
        ->and($hr->user->can('reject', $request))->toBeTrue();
});

it('人事はどの申請も承認・却下できる', function () {
    $hr = Employee::factory()->hr()->create();
    $request = TrainingRequest::factory()->create();

    expect($hr->user->can('approve', $request))->toBeTrue()
        ->and($hr->user->can('reject', $request))->toBeTrue();
});

it('無関係な従業員は申請を承認・却下できない', function () {
    $bystander = Employee::factory()->create();
    $request = TrainingRequest::factory()->create();

    expect($bystander->user->can('approve', $request))->toBeFalse()
        ->and($bystander->user->can('reject', $request))->toBeFalse();
});

it('申請者本人は自分の申請を承認できない', function () {
    $employee = Employee::factory()->create();
    $request = TrainingRequest::factory()->create(['employee_id' => $employee->id]);

    expect($employee->user->can('approve', $request))->toBeFalse();
});

it('申請者本人は承認待ちの申請を取り消せる', function () {
    $employee = Employee::factory()->create();
    $request = TrainingRequest::factory()->create(['employee_id' => $employee->id]);

    expect($employee->user->can('cancel', $request))->toBeTrue();
});

it('代理申請した上司は、その申請を取り消せる', function () {
    $manager = Employee::factory()->create();
    $subordinate = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $subordinate->id, 'manager_id' => $manager->id]);
    $request = TrainingRequest::factory()->create([
        'employee_id' => $subordinate->id,
        'requested_by_employee_id' => $manager->id,
    ]);

    expect($manager->user->can('cancel', $request))->toBeTrue();
});

it('上司であっても、部下の申請を代わりに取り消すことはできない', function () {
    $manager = Employee::factory()->create();
    $subordinate = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $subordinate->id, 'manager_id' => $manager->id]);
    $request = TrainingRequest::factory()->create(['employee_id' => $subordinate->id]);

    expect($manager->user->can('cancel', $request))->toBeFalse();
});

it('従業員レコードのないユーザーは申請を承認・却下できない', function () {
    $user = User::factory()->create();
    $request = TrainingRequest::factory()->create();

    expect($user->can('approve', $request))->toBeFalse()
        ->and($user->can('reject', $request))->toBeFalse();
});

it('従業員レコードのないユーザーは申請を取り消せない', function () {
    $user = User::factory()->create();
    $request = TrainingRequest::factory()->create();

    expect($user->can('cancel', $request))->toBeFalse();
});

it('多段階承認の1段階目は、申請対象本人の上司が承認・却下できる', function () {
    $manager = Employee::factory()->create();
    $subordinate = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $subordinate->id, 'manager_id' => $manager->id]);
    $request = TrainingRequest::factory()->multistage(2)->create(['employee_id' => $subordinate->id]);

    expect($manager->user->can('approve', $request))->toBeTrue()
        ->and($manager->user->can('reject', $request))->toBeTrue();
});

it('多段階承認の2段階目は、1段階目の決裁者の上司でなければ承認・却下できない', function () {
    $manager = Employee::factory()->create();
    $subordinate = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $subordinate->id, 'manager_id' => $manager->id]);
    $request = TrainingRequest::factory()->multistage(2)->create([
        'employee_id' => $subordinate->id,
        'current_approval_stage' => 2,
    ]);
    TrainingRequestApprovalStage::factory()->create([
        'training_request_id' => $request->id,
        'stage_number' => 1,
        'decided_by_employee_id' => $manager->id,
    ]);

    // 1段階目を決裁した本人（部長役）はもう決裁者ではない。
    expect($manager->user->can('approve', $request))->toBeFalse();

    $executive = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $manager->id, 'manager_id' => $executive->id]);

    // 1段階目の決裁者(部長)の上司(役員)が2段階目を承認・却下できる。
    expect($executive->user->can('approve', $request))->toBeTrue()
        ->and($executive->user->can('reject', $request))->toBeTrue();
});
