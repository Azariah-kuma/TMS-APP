<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use Laravel\Sanctum\Sanctum;

it('人事はレポートサマリーを閲覧できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);

    Sanctum::actingAs($hr->user);

    $this->getJson('/api/reports/training-summary')
        ->assertOk()
        ->assertJsonStructure(['by_training', 'by_department']);
});

it('一般社員はレポートサマリーを閲覧できない', function () {
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->getJson('/api/reports/training-summary')->assertForbidden();
});

it('人事は受講記録をCSVでエクスポートできる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);

    Sanctum::actingAs($hr->user);

    $response = $this->get('/api/reports/training-enrollments.csv')->assertOk();

    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="training_enrollments.csv"');
});

it('一般社員は受講記録のCSVエクスポートを利用できない', function () {
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->get('/api/reports/training-enrollments.csv')->assertForbidden();
});
