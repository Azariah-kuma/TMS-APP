<?php

declare(strict_types=1);

namespace App\Actions\Employees;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Throwable;

/**
 * 従業員に初期パスワード設定用の招待メールを送る。
 *
 * Laravel標準のパスワードリセット機構（Password::sendResetLink）は同期送信であり、
 * SMTP障害時などは例外を投げる。オンボーディング自体（User/Employee/初回配属の作成）は
 * メール送信の成否に関わらずコミット済みであるべきなので、失敗はここで握りつぶしてログに残し、
 * 呼び出し元には成否のみをbooleanで返す（再送はEmployeeController::resendInviteから行える）
 */
final class SendEmployeeInviteAction
{
    public function execute(string $email): bool
    {
        try {
            $status = Password::sendResetLink(['email' => $email]);

            if ($status !== Password::RESET_LINK_SENT) {
                Log::warning('従業員への招待メール送信がスキップされました。', [
                    'email' => $email,
                    'status' => $status,
                ]);

                return false;
            }

            return true;
        } catch (Throwable $exception) {
            Log::error('従業員への招待メール送信に失敗しました。', [
                'email' => $email,
                'exception' => $exception,
            ]);

            return false;
        }
    }
}
