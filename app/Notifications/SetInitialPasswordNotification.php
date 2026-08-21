<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 従業員オンボーディング時に送る、初期パスワード設定用の招待メール通知。
 *
 * Laravelのパスワードリセット機構（トークン発行・ハッシュ保存・有効期限）をそのまま流用し、
 * リンク先だけをフロントエンド（Angular SPA）の初期設定画面に向けている。
 */
final class SetInitialPasswordNotification extends Notification
{
    public function __construct(private readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $url = sprintf(
            '%s/set-password?token=%s&email=%s',
            rtrim(config('app.frontend_url'), '/'),
            $this->token,
            urlencode($notifiable->getEmailForPasswordReset()),
        );

        $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('【人事・研修管理】アカウントの初期設定のお願い')
            ->greeting("{$notifiable->name} 様")
            ->line('人事担当者があなたのアカウントを作成しました。以下のリンクからログインパスワードを設定してください。')
            ->action('パスワードを設定する', $url)
            ->line("このリンクの有効期限は{$expireMinutes}分です。")
            ->line('心当たりがない場合は、このメールを破棄してください。');
    }
}
