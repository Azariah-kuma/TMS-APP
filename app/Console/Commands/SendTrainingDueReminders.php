<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Trainings\SendTrainingDueRemindersAction;
use Illuminate\Console\Command;

final class SendTrainingDueReminders extends Command
{
    protected $signature = 'training:send-due-reminders';

    protected $description = '受講期限が明日で、まだ完了していない受講記録の本人にリマインドメールを送る';

    public function handle(SendTrainingDueRemindersAction $action): int
    {
        $count = $action->execute();

        $this->info("{$count}件のリマインドメールを送信しました。");

        return self::SUCCESS;
    }
}
