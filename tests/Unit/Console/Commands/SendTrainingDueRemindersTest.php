<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\TrainingEnrollment;
use Illuminate\Support\Facades\Notification;

it('training:send-due-reminders コマンドが正常終了する', function () {
    Notification::fake();

    $employee = Employee::factory()->create();
    TrainingEnrollment::factory()->create(['employee_id' => $employee->id, 'due_at' => now()->addDay()]);

    $this->artisan('training:send-due-reminders')->assertExitCode(0);
});
