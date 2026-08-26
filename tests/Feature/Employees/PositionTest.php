<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Position;
use Laravel\Sanctum\Sanctum;

it('人事はafter_position_idを省略すると最上位に役職を作成できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/positions', [
        'name' => '部長',
        'code' => 'POS-GM',
    ])->assertCreated()->assertJsonPath('name', '部長');

    $this->assertDatabaseHas('positions', ['code' => 'POS-GM', 'rank' => 1]);
});

it('既存の役職の直後に挿入すると、それ以降の役職のrankが繰り下がる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $sectionChief = Position::factory()->create(['name' => '係長', 'rank' => 1]);
    $seniorStaff = Position::factory()->create(['name' => '主任', 'rank' => 2]);

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/positions', [
        'name' => '班長',
        'code' => 'POS-TL',
        'after_position_id' => $sectionChief->id,
    ])->assertCreated()->assertJsonPath('rank', 2);

    expect($sectionChief->fresh()->rank)->toBe(1)
        ->and($seniorStaff->fresh()->rank)->toBe(3);
});

it('末尾の役職の直後に挿入すると、繰り下げ対象がなく末尾に追加される', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $last = Position::factory()->create(['rank' => 5]);

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/positions', [
        'name' => '新設役職',
        'code' => 'POS-NEW',
        'after_position_id' => $last->id,
    ])->assertCreated()->assertJsonPath('rank', 6);

    expect($last->fresh()->rank)->toBe(5);
});

it('一般社員は役職を新規作成できない', function () {
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->postJson('/api/positions', [
        'name' => '課長',
        'code' => 'POS-MGR',
    ])->assertForbidden();
});

it('役職コードが重複していると作成を拒否する', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    Position::factory()->create(['code' => 'POS-MGR']);

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/positions', [
        'name' => '課長',
        'code' => 'POS-MGR',
    ])->assertUnprocessable()->assertJsonValidationErrors('code');
});

it('存在しないafter_position_idを指定すると作成を拒否する', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/positions', [
        'name' => '課長',
        'code' => 'POS-MGR',
        'after_position_id' => 999999,
    ])->assertUnprocessable()->assertJsonValidationErrors('after_position_id');
});

it('人事は誤登録した役職名・役職コードを訂正できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $position = Position::factory()->create(['name' => '課長　', 'code' => 'POS-TYPO']);

    Sanctum::actingAs($hr->user);

    $this->patchJson("/api/positions/{$position->id}", [
        'name' => '課長',
        'code' => 'POS-MGR',
    ])->assertOk()->assertJsonPath('name', '課長');

    $this->assertDatabaseHas('positions', ['id' => $position->id, 'name' => '課長', 'code' => 'POS-MGR']);
});

it('更新時、他の役職と同じコードには変更できない', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    Position::factory()->create(['code' => 'POS-A']);
    $target = Position::factory()->create(['code' => 'POS-B']);

    Sanctum::actingAs($hr->user);

    $this->patchJson("/api/positions/{$target->id}", [
        'name' => $target->name,
        'code' => 'POS-A',
    ])->assertUnprocessable()->assertJsonValidationErrors('code');
});

it('更新時、自分自身と同じコードを指定してもエラーにならない', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $position = Position::factory()->create(['code' => 'POS-A']);

    Sanctum::actingAs($hr->user);

    $this->patchJson("/api/positions/{$position->id}", [
        'name' => '新しい名前',
        'code' => 'POS-A',
    ])->assertOk();
});

it('一般社員は役職を訂正できない', function () {
    $employee = createEmployeeWithAssignment();
    $position = Position::factory()->create();

    Sanctum::actingAs($employee->user);

    $this->patchJson("/api/positions/{$position->id}", [
        'name' => '新しい名前',
        'code' => $position->code,
    ])->assertForbidden();
});

it('人事は使われていない役職を削除できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $position = Position::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->deleteJson("/api/positions/{$position->id}")->assertNoContent();

    expect(Position::find($position->id))->toBeNull();
});

it('配属履歴で使用されている役職は削除できない', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $position = Position::factory()->create();
    EmployeeAssignment::factory()->create(['position_id' => $position->id]);

    Sanctum::actingAs($hr->user);

    $this->deleteJson("/api/positions/{$position->id}")->assertStatus(422);

    expect(Position::find($position->id))->not->toBeNull();
});

it('一般社員は役職を削除できない', function () {
    $employee = createEmployeeWithAssignment();
    $position = Position::factory()->create();

    Sanctum::actingAs($employee->user);

    $this->deleteJson("/api/positions/{$position->id}")->assertForbidden();
});

it('ログイン済みの従業員なら誰でも、役職一覧をrank順で閲覧できる', function () {
    $employee = Employee::factory()->create();
    Position::factory()->create(['rank' => 5]);
    Position::factory()->create(['rank' => 1]);

    Sanctum::actingAs($employee->user);

    $response = $this->getJson('/api/positions')->assertOk()->assertJsonCount(2);

    expect($response->json('0.rank'))->toBe(1)
        ->and($response->json('1.rank'))->toBe(5);
});

it('未ログインのゲストは役職一覧を閲覧できない', function () {
    $this->getJson('/api/positions')->assertUnauthorized();
});
