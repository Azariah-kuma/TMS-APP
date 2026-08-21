<?php

declare(strict_types=1);

use App\Actions\Employees\SendEmployeeInviteAction;
use App\Models\User;
use App\Notifications\SetInitialPasswordNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('招待メールを送信し、成功時はtrueを返す', function () {
    Notification::fake();

    $user = User::factory()->create();

    $sent = (new SendEmployeeInviteAction)->execute($user->email);

    expect($sent)->toBeTrue();
});

it('同一分以内の再送がLaravelのスロットルに引っかかった場合、偽の成功を返さずfalseを返す', function () {
    Notification::fake();

    $user = User::factory()->create();

    // 1回目は正常に送信される（＝実際にトークンが発行される）。
    expect((new SendEmployeeInviteAction)->execute($user->email))->toBeTrue();

    Log::shouldReceive('warning')->once()->with(
        '従業員への招待メール送信がスキップされました。',
        Mockery::on(fn (array $context) => $context['email'] === $user->email),
    );

    // 2回目は60秒以内のためLaravel標準のスロットルにより実際には送られない
    // （Password::sendResetLink()は例外を投げず、RESET_THROTTLEDという文字列を返すだけ）。
    // ここを見ずに常にtrueを返すと、実際には届いていないのに「送信しました」と表示してしまう。
    expect((new SendEmployeeInviteAction)->execute($user->email))->toBeFalse();

    Notification::assertSentTimes(SetInitialPasswordNotification::class, 1);
});

it('通知の配信に失敗した場合、ログに記録しfalseを返す', function () {
    Password::shouldReceive('sendResetLink')
        ->once()
        ->andThrow(new RuntimeException('SMTP connection refused'));

    Log::shouldReceive('error')
        ->once()
        ->with('従業員への招待メール送信に失敗しました。', Mockery::on(
            fn (array $context) => $context['email'] === 'taro@example.com'
                && $context['exception'] instanceof RuntimeException,
        ));

    $sent = (new SendEmployeeInviteAction)->execute('taro@example.com');

    expect($sent)->toBeFalse();
});
