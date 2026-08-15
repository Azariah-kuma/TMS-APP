<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Training;
use Laravel\Sanctum\Sanctum;

it('lets hr create a training', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/trainings', [
        'title' => '情報セキュリティ研修',
        'category' => '情報セキュリティ',
    ])->assertCreated()->assertJsonPath('title', '情報セキュリティ研修');
});

it('forbids a general employee from creating a training', function () {
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->postJson('/api/trainings', ['title' => '情報セキュリティ研修'])->assertForbidden();
});

it('allows any authenticated employee to browse the training catalog', function () {
    $employee = createEmployeeWithAssignment();
    Training::factory()->count(2)->create();

    Sanctum::actingAs($employee->user);

    $this->getJson('/api/trainings')->assertOk()->assertJsonCount(2);
});
