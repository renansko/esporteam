<?php

namespace App\Services;

use App\Models\EventMessage;

final readonly class NotificationActivity
{
    public function __construct(
        public string $type,
        public EventMessage $message,
        public ?int $recipientProfileId = null,
    ) {}
}
