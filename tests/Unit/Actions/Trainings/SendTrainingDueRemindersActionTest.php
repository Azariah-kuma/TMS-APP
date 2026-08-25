<?php

declare(strict_types=1);

use App\Actions\Trainings\SendTrainingDueRemindersAction;
use App\Enums\TrainingEnrollmentStatus;
use App\Models\Employee;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use App\Notifications\TrainingDueReminderNotification;
use Illuminate\Support\Facades\Notification;

it('受講期限が明日で未完了の従業員にリマインドメールを送る', function () {
    Notification::fake();

    $employee = Employee::factory()->create();
    $training = Training::factory()->create();
    TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'training_id' => $training->id,
        'due_at' => now()->addDay(),
    ]);

    $count = (new SendTrainingDueRemindersAction)->execute();

    expect($count)->toBe(1);
    Notification::assertSentTo(
        $employee->user,
        TrainingDueReminderNotification::class,
        function (TrainingDueReminderNotification $notification) use ($employee, $training) {
            $mail = $notification->toMail($employee->user);

            return str_contains($mail->subject, $training->title);
        },
    );
});

it('受講期限が明日以外の受講記録には送らない', function () {
    Notification::fake();

    $today = Employee::factory()->create();
    TrainingEnrollment::factory()->create(['employee_id' => $today->id, 'due_at' => now()]);

    $dayAfterTomorrow = Employee::factory()->create();
    TrainingEnrollment::factory()->create(['employee_id' => $dayAfterTomorrow->id, 'due_at' => now()->addDays(2)]);

    $count = (new SendTrainingDueRemindersAction)->execute();

    expect($count)->toBe(0);
    Notification::assertNothingSent();
});

it('既に完了している受講記録には送らない', function () {
    Notification::fake();

    $employee = Employee::factory()->create();
    TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'due_at' => now()->addDay(),
        'status' => TrainingEnrollmentStatus::Completed,
    ]);

    $count = (new SendTrainingDueRemindersAction)->execute();

    expect($count)->toBe(0);
});
