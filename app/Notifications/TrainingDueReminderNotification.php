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
 * 受講期限の前日に、未完了の本人へ知らせるリマインド通知。
 */
final class TrainingDueReminderNotification extends Notification implements ShouldQueue
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
        $dueAt = $this->enrollment->due_at?->toDateString();
        $url = sprintf(
            '%s/enrollments/%d',
            rtrim(config('app.frontend_url'), '/'),
            $this->enrollment->id,
        );

        return (new MailMessage)
            ->subject("【人事・研修管理】研修「{$title}」の受講期限は明日です")
            ->greeting("{$notifiable->name} 様")
            ->line("研修「{$title}」の受講期限（{$dueAt}）が明日に迫っています。まだ完了していない場合は、早めに進めてください。")
            ->action('受講状況を見る', $url);
    }
}
