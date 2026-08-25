<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TrainingEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * 研修の受講登録が作成され、受講可能になったことを本人へ知らせる通知。
 */
final class TrainingEnrollmentCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly TrainingEnrollment $enrollment) {}

    /**
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $title = $this->enrollment->training->title;
        $url = sprintf(
            '%s/enrollments/%d',
            rtrim(config('app.frontend_url'), '/'),
            $this->enrollment->id,
        );

        $message = (new MailMessage)
            ->subject("【人事・研修管理】研修「{$title}」の受講が可能になりました")
            ->greeting("{$notifiable->name} 様")
            ->line("研修「{$title}」の受講登録が完了しました。受講が可能になりましたので、都合の良いタイミングで進めてください。");

        if ($this->enrollment->due_at !== null) {
            $message->line("受講期限: {$this->enrollment->due_at->toDateString()}");
        }

        return $message->action('受講状況を見る', $url);
    }
}
