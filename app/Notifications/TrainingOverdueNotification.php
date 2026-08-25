<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\TrainingOverdueRecipient;
use App\Models\TrainingEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * 受講期限を過ぎても未完了の場合に送る通知。
 */
final class TrainingOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly TrainingEnrollment $enrollment,
        private readonly TrainingOverdueRecipient $recipientContext,
    ) {}

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
        $employeeName = $this->enrollment->employee->user->name;
        $dueAt = $this->enrollment->due_at?->toDateString();

        $line = match ($this->recipientContext) {
            TrainingOverdueRecipient::Self => "研修「{$title}」の受講期限（{$dueAt}）を過ぎましたが、まだ完了していません。早めに完了してください。",
            TrainingOverdueRecipient::Manager => "部下の{$employeeName}さんが、研修「{$title}」の受講期限（{$dueAt}）を過ぎても完了していません。",
            TrainingOverdueRecipient::Hr => "{$employeeName}さんが、研修「{$title}」の受講期限（{$dueAt}）を過ぎても完了していません。",
        };

        return (new MailMessage)
            ->subject("【人事・研修管理】研修「{$title}」が期限切れです")
            ->greeting("{$notifiable->name} 様")
            ->line($line);
    }
}
