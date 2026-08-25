<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\TrainingEnrollment;
use Illuminate\Support\Facades\Notification;

it('training:send-overdue-notices コマンドが正常終了する', function () {
    Notification::fake();

    $employee = Employee::factory()->create();
    TrainingEnrollment::factory()->create(['employee_id' => $employee->id, 'due_at' => now()->subDay()]);

    $this->artisan('training:send-overdue-notices')->assertExitCode(0);
});
