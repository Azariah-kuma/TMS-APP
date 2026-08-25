<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Trainings\SendTrainingOverdueNoticesAction;
use Illuminate\Console\Command;

final class SendTrainingOverdueNotices extends Command
{
    protected $signature = 'training:send-overdue-notices';

    protected $description = '受講期限が昨日で、まだ完了していない受講記録について本人・上司・人事に期限切れを知らせる';

    public function handle(SendTrainingOverdueNoticesAction $action): int
    {
        $count = $action->execute();

        $this->info("{$count}件の期限切れ通知を送信しました。");

        return self::SUCCESS;
    }
}
