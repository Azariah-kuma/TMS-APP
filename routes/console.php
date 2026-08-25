<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 実際に毎分 schedule:run を呼び出す仕組み（本番サーバーのcron登録）は別途必要。
Schedule::command('training:send-due-reminders')->dailyAt('09:00');
Schedule::command('training:send-overdue-notices')->dailyAt('09:00');
