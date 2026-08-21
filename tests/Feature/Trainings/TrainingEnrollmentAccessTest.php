<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Department;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use Laravel\Sanctum\Sanctum;

it('一般社員には自分の受講記録しか表示されない', function () {
    $employee = createEmployeeWithAssignment();
    $other = createEmployeeWithAssignment();

    $mine = TrainingEnrollment::factory()->create(['employee_id' => $employee->id]);
    TrainingEnrollment::factory()->create(['employee_id' => $other->id]);

    Sanctum::actingAs($employee->user);

    $ids = collect($this->getJson('/api/training-enrollments')->assertOk()->json())->pluck('id');

    expect($ids->all())->toBe([$mine->id]);
});

it('上司には自分と部下の受講記録のみ表示される', function () {
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $unrelated = createEmployeeWithAssignment();

    $managerEnrollment = TrainingEnrollment::factory()->create(['employee_id' => $manager->id]);
    $subordinateEnrollment = TrainingEnrollment::factory()->create(['employee_id' => $subordinate->id]);
    TrainingEnrollment::factory()->create(['employee_id' => $unrelated->id]);

    Sanctum::actingAs($manager->user);

    $ids = collect($this->getJson('/api/training-enrollments')->assertOk()->json())
        ->pluck('id')->sort()->values();

    expect($ids->all())->toBe(collect([$managerEnrollment->id, $subordinateEnrollment->id])->sort()->values()->all());
});

it('人事には全ての受講記録が表示される', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    TrainingEnrollment::factory()->count(3)->create();

    Sanctum::actingAs($hr->user);

    $this->getJson('/api/training-enrollments')->assertOk()->assertJsonCount(3);
});

it('上司は部下の進捗を閲覧できるが更新はできない', function () {
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $subordinate->id]);

    Sanctum::actingAs($manager->user);

    $this->getJson("/api/training-enrollments/{$enrollment->id}")->assertOk();
    $this->patchJson("/api/training-enrollments/{$enrollment->id}", ['progress' => 50])->assertForbidden();
});

it('従業員は自分の進捗を更新できる', function () {
    $employee = createEmployeeWithAssignment();
    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $employee->id]);

    Sanctum::actingAs($employee->user);

    $this->patchJson("/api/training-enrollments/{$enrollment->id}", ['progress' => 40])
        ->assertOk()
        ->assertJsonPath('progress', 40)
        ->assertJsonPath('status', 'in_progress');
});

it('無関係な従業員は他人の受講記録を閲覧できない', function () {
    $employee = createEmployeeWithAssignment();
    $other = createEmployeeWithAssignment();
    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $other->id]);

    Sanctum::actingAs($employee->user);

    $this->getJson("/api/training-enrollments/{$enrollment->id}")->assertForbidden();
});

it('人事は従業員を研修に割り当てられる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->postJson("/api/employees/{$employee->id}/training-enrollments", [
        'training_id' => $training->id,
    ])->assertCreated();
});

it('一般社員は自分自身を研修に割り当てられない', function () {
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();

    Sanctum::actingAs($employee->user);

    $this->postJson("/api/employees/{$employee->id}/training-enrollments", [
        'training_id' => $training->id,
    ])->assertForbidden();
});

it('人事は受講登録を取り消せる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $enrollment = TrainingEnrollment::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->deleteJson("/api/training-enrollments/{$enrollment->id}")->assertNoContent();

    expect(TrainingEnrollment::find($enrollment->id))->toBeNull();
});

it('受講登録された本人であっても、自分の受講登録は取り消せない', function () {
    $employee = createEmployeeWithAssignment();
    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $employee->id]);

    Sanctum::actingAs($employee->user);

    $this->deleteJson("/api/training-enrollments/{$enrollment->id}")->assertForbidden();

    expect(TrainingEnrollment::find($enrollment->id))->not->toBeNull();
});

it('人事は部署単位で一括受講登録できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $department = Department::factory()->create();
    $inDepartment = createEmployeeWithAssignment(assignmentAttributes: ['department_id' => $department->id]);
    $elsewhere = createEmployeeWithAssignment();
    $training = Training::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->postJson("/api/trainings/{$training->id}/bulk-enroll", [
        'department_id' => $department->id,
    ])->assertOk()->assertJson(['enrolled' => 1, 'skipped' => 0]);

    expect(TrainingEnrollment::where('employee_id', $inDepartment->id)->exists())->toBeTrue()
        ->and(TrainingEnrollment::where('employee_id', $elsewhere->id)->exists())->toBeFalse();
});

it('部署を指定しない場合、人事は全社一括で受講登録できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    createEmployeeWithAssignment();
    createEmployeeWithAssignment();
    $training = Training::factory()->create();

    Sanctum::actingAs($hr->user);

    // hr自身を含め、在籍中の全従業員が対象になる。
    $response = $this->postJson("/api/trainings/{$training->id}/bulk-enroll", [])->assertOk();

    expect($response->json('enrolled'))->toBe(3);
});

it('一般社員は誰も一括受講登録できない', function () {
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();

    Sanctum::actingAs($employee->user);

    $this->postJson("/api/trainings/{$training->id}/bulk-enroll", [])->assertForbidden();
});
