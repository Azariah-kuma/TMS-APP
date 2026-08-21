<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Delegation;
use App\Models\TrainingEnrollment;
use Laravel\Sanctum\Sanctum;

it('人事が委任を作成すると、代理者は委任元の部下を閲覧できるようになる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $delegate = createEmployeeWithAssignment();

    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $subordinate->id]);

    Sanctum::actingAs($delegate->user);
    $this->getJson("/api/training-enrollments/{$enrollment->id}")->assertForbidden();

    Sanctum::actingAs($hr->user);
    $this->postJson("/api/employees/{$manager->id}/delegations", [
        'delegate_id' => $delegate->id,
        'started_at' => now()->toDateString(),
    ])->assertCreated();

    Sanctum::actingAs($delegate->user);
    $this->getJson("/api/training-enrollments/{$enrollment->id}")->assertOk();
});

it('一般社員は委任を作成できない', function () {
    $manager = createEmployeeWithAssignment();
    $delegate = createEmployeeWithAssignment();

    Sanctum::actingAs($manager->user);

    $this->postJson("/api/employees/{$manager->id}/delegations", [
        'delegate_id' => $delegate->id,
        'started_at' => now()->toDateString(),
    ])->assertForbidden();
});

it('人事が委任を取り消すと、代理者の閲覧権限は即座に失われる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $delegate = createEmployeeWithAssignment();

    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $subordinate->id]);

    $delegation = Delegation::factory()->create([
        'delegator_id' => $manager->id,
        'delegate_id' => $delegate->id,
        'started_at' => now()->subDay(),
        'ended_at' => null,
    ]);

    Sanctum::actingAs($delegate->user);
    $this->getJson("/api/training-enrollments/{$enrollment->id}")->assertOk();

    Sanctum::actingAs($hr->user);
    $this->deleteJson("/api/delegations/{$delegation->id}")->assertOk();

    Sanctum::actingAs($delegate->user);
    $this->getJson("/api/training-enrollments/{$enrollment->id}")->assertForbidden();
});

it('委任元は自分が行った委任の一覧を取得できる', function () {
    $manager = createEmployeeWithAssignment();
    $delegate = createEmployeeWithAssignment();

    $delegation = Delegation::factory()->create([
        'delegator_id' => $manager->id,
        'delegate_id' => $delegate->id,
    ]);

    Sanctum::actingAs($manager->user);

    $this->getJson("/api/employees/{$manager->id}/delegations")
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $delegation->id)
        ->assertJsonPath('0.delegate_id', $delegate->id);
});

it('無関係な従業員は他人の委任一覧を取得できない', function () {
    $manager = createEmployeeWithAssignment();
    $bystander = createEmployeeWithAssignment();

    Sanctum::actingAs($bystander->user);

    $this->getJson("/api/employees/{$manager->id}/delegations")->assertForbidden();
});

it('代理者は部下の進捗を更新できない（上司と同じく閲覧のみ）', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $delegate = createEmployeeWithAssignment();

    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $subordinate->id]);

    Delegation::factory()->create([
        'delegator_id' => $manager->id,
        'delegate_id' => $delegate->id,
        'started_at' => now()->subDay(),
        'ended_at' => null,
    ]);

    Sanctum::actingAs($delegate->user);

    $this->patchJson("/api/training-enrollments/{$enrollment->id}", ['progress' => 50])->assertForbidden();
});
