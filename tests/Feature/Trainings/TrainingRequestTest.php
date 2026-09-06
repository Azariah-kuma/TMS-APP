<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Training;
use App\Models\TrainingRequest;
use Laravel\Sanctum\Sanctum;

it('従業員は自分自身の研修受講を申請できる', function () {
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();

    Sanctum::actingAs($employee->user);

    $response = $this->postJson('/api/training-requests', [
        'training_id' => $training->id,
        'reason' => '業務に必要なため',
    ])->assertCreated();

    $response->assertJsonPath('employee_id', $employee->id)
        ->assertJsonPath('status', 'pending')
        ->assertJsonPath('reason', '業務に必要なため');

    $this->assertDatabaseHas('training_requests', [
        'employee_id' => $employee->id,
        'training_id' => $training->id,
        'status' => 'pending',
    ]);
});

it('同じ研修への重複申請は拒否される', function () {
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();

    Sanctum::actingAs($employee->user);

    $this->postJson('/api/training-requests', ['training_id' => $training->id])->assertCreated();
    $this->postJson('/api/training-requests', ['training_id' => $training->id])->assertStatus(422);
});

it('上司には部下の申請が一覧に表示され、無関係な従業員の申請は表示されない', function () {
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $unrelated = createEmployeeWithAssignment();

    $mine = TrainingRequest::factory()->create(['employee_id' => $subordinate->id]);
    TrainingRequest::factory()->create(['employee_id' => $unrelated->id]);

    Sanctum::actingAs($manager->user);

    $ids = collect($this->getJson('/api/training-requests')->assertOk()->json())->pluck('id');

    expect($ids->all())->toBe([$mine->id]);
});

it('人事には全ての申請が表示される', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    TrainingRequest::factory()->count(3)->create();

    Sanctum::actingAs($hr->user);

    $this->getJson('/api/training-requests')->assertOk()->assertJsonCount(3);
});

it('上司は部下の申請を個別に閲覧できる', function () {
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $request = TrainingRequest::factory()->create(['employee_id' => $subordinate->id]);

    Sanctum::actingAs($manager->user);

    $this->getJson("/api/training-requests/{$request->id}")
        ->assertOk()
        ->assertJsonPath('id', $request->id);
});

it('上司は部下の申請を承認でき、受講記録が作られる', function () {
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $training = Training::factory()->create();
    $request = TrainingRequest::factory()->create(['employee_id' => $subordinate->id, 'training_id' => $training->id]);

    Sanctum::actingAs($manager->user);

    $this->postJson("/api/training-requests/{$request->id}/approve")
        ->assertOk()
        ->assertJsonPath('status', 'approved');

    $enrollmentIds = collect($this->getJson('/api/training-enrollments')->assertOk()->json())->pluck('training.id');
    expect($enrollmentIds->all())->toBe([$training->id]);
});

it('上司は部下の申請を却下できる', function () {
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $request = TrainingRequest::factory()->create(['employee_id' => $subordinate->id]);

    Sanctum::actingAs($manager->user);

    $this->postJson("/api/training-requests/{$request->id}/reject", ['comment' => '時期尚早のため'])
        ->assertOk()
        ->assertJsonPath('status', 'rejected')
        ->assertJsonPath('decision_comment', '時期尚早のため');
});

it('申請者本人は承認待ちの申請を取り消せる', function () {
    $employee = createEmployeeWithAssignment();
    $request = TrainingRequest::factory()->create(['employee_id' => $employee->id]);

    Sanctum::actingAs($employee->user);

    $this->deleteJson("/api/training-requests/{$request->id}")
        ->assertOk()
        ->assertJsonPath('status', 'cancelled');
});

it('無関係な従業員は他人の申請を承認・却下・取消できない', function () {
    $bystander = createEmployeeWithAssignment();
    $request = TrainingRequest::factory()->create();

    Sanctum::actingAs($bystander->user);

    $this->postJson("/api/training-requests/{$request->id}/approve")->assertForbidden();
    $this->postJson("/api/training-requests/{$request->id}/reject")->assertForbidden();
    $this->deleteJson("/api/training-requests/{$request->id}")->assertForbidden();
});

it('承認待ちでない申請への承認操作は拒否される', function () {
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $request = TrainingRequest::factory()->approved()->create(['employee_id' => $subordinate->id]);

    Sanctum::actingAs($manager->user);

    $this->postJson("/api/training-requests/{$request->id}/approve")->assertStatus(422);
});

it('人事は誰の申請でも承認できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $employee = createEmployeeWithAssignment();
    $request = TrainingRequest::factory()->create(['employee_id' => $employee->id]);

    Sanctum::actingAs($hr->user);

    $this->postJson("/api/training-requests/{$request->id}/approve")->assertOk();
});

it('上司は部下を対象に代理で研修受講を申請できる', function () {
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $training = Training::factory()->create();

    Sanctum::actingAs($manager->user);

    $response = $this->postJson('/api/training-requests', [
        'employee_id' => $subordinate->id,
        'training_id' => $training->id,
    ])->assertCreated();

    $response->assertJsonPath('employee_id', $subordinate->id)
        ->assertJsonPath('requested_by_employee_id', $manager->id)
        ->assertJsonPath('is_self_requested', false);
});

it('無関係な従業員を対象にした代理申請は拒否される', function () {
    $bystander = createEmployeeWithAssignment();
    $someone = createEmployeeWithAssignment();
    $training = Training::factory()->create();

    Sanctum::actingAs($bystander->user);

    $this->postJson('/api/training-requests', [
        'employee_id' => $someone->id,
        'training_id' => $training->id,
    ])->assertForbidden();
});

it('上司による代理申請は、申請元の上司自身も承認できず、人事の承認が必要になる', function () {
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $training = Training::factory()->create();

    Sanctum::actingAs($manager->user);
    $created = $this->postJson('/api/training-requests', [
        'employee_id' => $subordinate->id,
        'training_id' => $training->id,
    ])->assertCreated()->json();

    $this->postJson("/api/training-requests/{$created['id']}/approve")->assertForbidden();

    Sanctum::actingAs($hr->user);
    $this->postJson("/api/training-requests/{$created['id']}/approve")
        ->assertOk()
        ->assertJsonPath('status', 'approved');
});

it('代理申請した上司は、その申請を取り消せる', function () {
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $request = TrainingRequest::factory()->create([
        'employee_id' => $subordinate->id,
        'requested_by_employee_id' => $manager->id,
    ]);

    Sanctum::actingAs($manager->user);

    $this->deleteJson("/api/training-requests/{$request->id}")
        ->assertOk()
        ->assertJsonPath('status', 'cancelled');
});

it('上司は自部署の部下をまとめて研修申請できる', function () {
    $manager = createEmployeeWithAssignment();
    $department = $manager->currentAssignment->department_id;
    $subordinate = createEmployeeWithAssignment(
        assignmentAttributes: ['manager_id' => $manager->id, 'department_id' => $department],
    );
    $training = Training::factory()->create();

    Sanctum::actingAs($manager->user);

    $this->postJson("/api/trainings/{$training->id}/bulk-request", ['department_id' => $department])
        ->assertOk()
        ->assertJson(['requested' => 1, 'skipped' => 0]);

    $this->assertDatabaseHas('training_requests', [
        'employee_id' => $subordinate->id,
        'requested_by_employee_id' => $manager->id,
    ]);
});

it('人事は複数の申請をまとめて承認できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $requestA = TrainingRequest::factory()->create();
    $requestB = TrainingRequest::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/training-requests/bulk-approve', ['ids' => [$requestA->id, $requestB->id]])
        ->assertOk()
        ->assertJson(['approved' => 2, 'skipped' => 0]);

    expect($requestA->fresh()->status->value)->toBe('approved')
        ->and($requestB->fresh()->status->value)->toBe('approved');
});

it('一括承認で同じIDが重複していても、重複分はスキップ件数に含めない', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $request = TrainingRequest::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/training-requests/bulk-approve', ['ids' => [$request->id, $request->id]])
        ->assertOk()
        ->assertJson(['approved' => 1, 'skipped' => 0]);
});

it('一括承認では、承認権限のない申請や承認待ちでない申請はスキップされる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $pending = TrainingRequest::factory()->create();
    $alreadyApproved = TrainingRequest::factory()->approved()->create();

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/training-requests/bulk-approve', ['ids' => [$pending->id, $alreadyApproved->id]])
        ->assertOk()
        ->assertJson(['approved' => 1, 'skipped' => 1]);
});

it('無関係な従業員が一括承認を実行しても、権限のない申請は全てスキップされる', function () {
    $bystander = createEmployeeWithAssignment();
    $request = TrainingRequest::factory()->create();

    Sanctum::actingAs($bystander->user);

    $this->postJson('/api/training-requests/bulk-approve', ['ids' => [$request->id]])
        ->assertOk()
        ->assertJson(['approved' => 0, 'skipped' => 1]);

    expect($request->fresh()->status->value)->toBe('pending');
});

it('部下がいない一般社員は部署一括申請を拒否される', function () {
    $employee = createEmployeeWithAssignment();
    $department = $employee->currentAssignment->department_id;
    $training = Training::factory()->create();

    Sanctum::actingAs($employee->user);

    $this->postJson("/api/trainings/{$training->id}/bulk-request", ['department_id' => $department])
        ->assertForbidden();
});

it('多段階承認が必要な研修は、部長→役員の順に承認されないと最終承認にならない', function () {
    $executive = createEmployeeWithAssignment();
    $manager = createEmployeeWithAssignment([], ['manager_id' => $executive->id]);
    $employee = createEmployeeWithAssignment([], ['manager_id' => $manager->id]);
    $training = Training::factory()->create([
        'requires_multistage_approval' => true,
        'approval_stage_count' => 2,
    ]);

    Sanctum::actingAs($employee->user);
    $this->postJson('/api/training-requests', ['training_id' => $training->id])->assertCreated();
    $request = TrainingRequest::where('employee_id', $employee->id)->firstOrFail();

    // 1段階目: 直属の上司（部長役）が承認しても、まだ承認待ちのまま。
    Sanctum::actingAs($manager->user);
    $this->postJson("/api/training-requests/{$request->id}/approve")
        ->assertOk()
        ->assertJsonPath('status', 'pending')
        ->assertJsonPath('current_approval_stage', 2)
        ->assertJsonPath('approval_history.0.stage_number', 1);

    // 部長自身は2段階目を承認できない（既に決裁済みのため）。
    $this->postJson("/api/training-requests/{$request->id}/approve")->assertForbidden();

    // 2段階目: 部長の上司（役員役）が承認して、初めて確定する。
    Sanctum::actingAs($executive->user);
    $this->postJson("/api/training-requests/{$request->id}/approve")
        ->assertOk()
        ->assertJsonPath('status', 'approved')
        ->assertJsonPath('approval_history.1.stage_number', 2);

    $enrollmentIds = collect($this->getJson('/api/training-enrollments')->assertOk()->json())->pluck('training.id');
    expect($enrollmentIds->all())->toBe([$training->id]);
});

it('多段階承認が必要な研修は、途中の段階で却下されると残りの段階を待たずに却下確定する', function () {
    $manager = createEmployeeWithAssignment();
    $employee = createEmployeeWithAssignment([], ['manager_id' => $manager->id]);
    $training = Training::factory()->create([
        'requires_multistage_approval' => true,
        'approval_stage_count' => 2,
    ]);
    $request = TrainingRequest::factory()->multistage(2)->create([
        'employee_id' => $employee->id,
        'requested_by_employee_id' => $employee->id,
        'training_id' => $training->id,
    ]);

    Sanctum::actingAs($manager->user);

    $this->postJson("/api/training-requests/{$request->id}/reject", ['comment' => '今回は見送り'])
        ->assertOk()
        ->assertJsonPath('status', 'rejected')
        ->assertJsonPath('decision_comment', '今回は見送り');
});
