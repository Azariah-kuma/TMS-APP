<?php

declare(strict_types=1);

use App\Actions\Trainings\SendTrainingOverdueNoticesAction;
use App\Enums\TrainingEnrollmentStatus;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use App\Notifications\TrainingOverdueNotification;
use Illuminate\Support\Facades\Notification;

it('受講期限が昨日で未完了の場合、本人・上司・人事に期限切れを通知する', function () {
    Notification::fake();

    $manager = Employee::factory()->create();
    $employee = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $employee->id, 'manager_id' => $manager->id]);
    $hr = Employee::factory()->hr()->create();

    $training = Training::factory()->create();
    TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'training_id' => $training->id,
        'due_at' => now()->subDay(),
    ]);

    $count = (new SendTrainingOverdueNoticesAction)->execute();

    expect($count)->toBe(3);

    $rendersMailAbout = fn (Employee $recipientEmployee) => function (TrainingOverdueNotification $notification) use ($recipientEmployee, $training) {
        $mail = $notification->toMail($recipientEmployee->user);

        return str_contains($mail->subject, $training->title);
    };

    Notification::assertSentTo($employee->user, TrainingOverdueNotification::class, $rendersMailAbout($employee));
    Notification::assertSentTo($manager->user, TrainingOverdueNotification::class, $rendersMailAbout($manager));
    Notification::assertSentTo($hr->user, TrainingOverdueNotification::class, $rendersMailAbout($hr));
});

it('上司がいない場合は本人と人事のみに通知する', function () {
    Notification::fake();

    $employee = Employee::factory()->create();
    EmployeeAssignment::factory()->create(['employee_id' => $employee->id, 'manager_id' => null]);
    Employee::factory()->hr()->create();

    TrainingEnrollment::factory()->create(['employee_id' => $employee->id, 'due_at' => now()->subDay()]);

    $count = (new SendTrainingOverdueNoticesAction)->execute();

    expect($count)->toBe(2);
});

it('受講期限が昨日以外の受講記録には送らない', function () {
    Notification::fake();

    $today = Employee::factory()->create();
    TrainingEnrollment::factory()->create(['employee_id' => $today->id, 'due_at' => now()]);

    $twoDaysAgo = Employee::factory()->create();
    TrainingEnrollment::factory()->create(['employee_id' => $twoDaysAgo->id, 'due_at' => now()->subDays(2)]);

    $count = (new SendTrainingOverdueNoticesAction)->execute();

    expect($count)->toBe(0);
    Notification::assertNothingSent();
});

it('既に完了している受講記録には送らない', function () {
    Notification::fake();

    $employee = Employee::factory()->create();
    TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'due_at' => now()->subDay(),
        'status' => TrainingEnrollmentStatus::Completed,
    ]);

    $count = (new SendTrainingOverdueNoticesAction)->execute();

    expect($count)->toBe(0);
});
