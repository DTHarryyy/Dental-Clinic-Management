<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Ramsey\Uuid\Uuid;

class PatientPortalAlert extends Notification
{
    public function __construct(
        public string $event,
        public string $title,
        public string $message,
        public string $url,
        public ?int $relatedId = null,
        public ?string $recipientKey = null,
    ) {
        $this->id = (string) Uuid::uuid5(
            Uuid::NAMESPACE_URL,
            "portal-alert/{$event}/{$relatedId}/{$url}/{$recipientKey}",
        );
    }

    public function via(User $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(User $notifiable): string
    {
        return $this->event;
    }

    public function toDatabase(User $notifiable): array
    {
        return [
            'type' => $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'related_id' => $this->relatedId,
        ];
    }
}
