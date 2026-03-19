<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\SystemMessageNotification;
use Illuminate\Database\Eloquent\Model;

class NotificationDispatcher
{
    /**
     * @param  iterable<int, User>  $recipients
     */
    public function send(
        iterable $recipients,
        string $category,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        string $level = 'info',
        ?User $sender = null,
        ?Model $context = null,
    ): void {
        foreach ($recipients as $recipient) {
            $recipient->notify(new SystemMessageNotification(
                title: $title,
                message: $message,
                actionUrl: $actionUrl,
                actionLabel: $actionLabel,
                level: $level,
            ));

            $recipient->notificationLogs()->create([
                'sent_by' => $sender?->id,
                'category' => $category,
                'channel' => 'database',
                'title' => $title,
                'message' => $message,
                'action_url' => $actionUrl,
                'action_label' => $actionLabel,
                'level' => $level,
                'context_type' => $context?->getMorphClass(),
                'context_id' => $context?->getKey(),
                'sent_at' => now(),
            ]);
        }
    }

    public function sendToUser(
        User $recipient,
        string $category,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        string $level = 'info',
        ?User $sender = null,
        ?Model $context = null,
    ): void {
        $this->send(
            recipients: [$recipient],
            category: $category,
            title: $title,
            message: $message,
            actionUrl: $actionUrl,
            actionLabel: $actionLabel,
            level: $level,
            sender: $sender,
            context: $context,
        );
    }
}
