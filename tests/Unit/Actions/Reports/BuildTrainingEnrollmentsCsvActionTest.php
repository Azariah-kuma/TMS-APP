<?php

declare(strict_types=1);

use App\Actions\Reports\BuildTrainingEnrollmentsCsvAction;
use App\Enums\TrainingEnrollmentStatus;
use App\Models\Department;
use App\Models\Position;
use App\Models\Training;
use App\Models\TrainingEnrollment;

it('受講記録をヘッダー付きCSVとして出力する', function () {
    $department = Department::factory()->create(['name' => '開発部']);
    $position = Position::factory()->create(['name' => '主任']);
    $employee = createEmployeeWithAssignment(
        ['employee_code' => 'EMP-0001'],
        ['department_id' => $department->id, 'position_id' => $position->id],
    );
    $training = Training::factory()->create(['title' => '情報セキュリティ研修']);
    TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'training_id' => $training->id,
        'status' => TrainingEnrollmentStatus::Completed,
        'progress' => 100,
        'due_at' => '2026-06-01',
        'completed_at' => '2026-05-20 10:00:00',
    ]);

    $csv = app(BuildTrainingEnrollmentsCsvAction::class)->execute();
    $lines = array_map('str_getcsv', explode("\n", trim($csv)));

    expect($lines[0])->toBe(['従業員コード', '氏名', '部署', '役職', '研修', 'ステータス', '進捗', '期限', '完了日時'])
        ->and($lines[1])->toBe([
            'EMP-0001',
            $employee->fresh()->user->name,
            '開発部',
            '主任',
            '情報セキュリティ研修',
            '完了',
            '100',
            '2026-06-01',
            '2026-05-20',
        ]);
});

it('部署名・研修タイトルが数式で始まる場合、Excel等で実行されないようシングルクォートを前置する', function () {
    $department = Department::factory()->create(['name' => '=HYPERLINK("http://evil.example/")']);
    $employee = createEmployeeWithAssignment([], ['department_id' => $department->id]);
    $training = Training::factory()->create(['title' => '+SUM(1,1)']);
    TrainingEnrollment::factory()->create(['employee_id' => $employee->id, 'training_id' => $training->id]);

    $csv = app(BuildTrainingEnrollmentsCsvAction::class)->execute();
    $lines = array_map('str_getcsv', explode("\n", trim($csv)));

    expect($lines[1][2])->toBe('\'=HYPERLINK("http://evil.example/")')
        ->and($lines[1][4])->toBe('\'+SUM(1,1)');
});

it('数式で始まらない値はそのまま出力する', function () {
    $department = Department::factory()->create(['name' => '開発部']);
    $employee = createEmployeeWithAssignment([], ['department_id' => $department->id]);
    TrainingEnrollment::factory()->create(['employee_id' => $employee->id]);

    $csv = app(BuildTrainingEnrollmentsCsvAction::class)->execute();
    $lines = array_map('str_getcsv', explode("\n", trim($csv)));

    expect($lines[1][2])->toBe('開発部');
});

it('受講記録が1件もない場合はヘッダー行のみになる', function () {
    $csv = app(BuildTrainingEnrollmentsCsvAction::class)->execute();
    $lines = array_map('str_getcsv', explode("\n", trim($csv)));

    expect($lines)->toHaveCount(1);
});
