<?php

namespace App\Enums;

enum SubscriberStatus: string
{
    case Pending = 'pending';
    case Subscribed = 'subscribed';
    case Unsubscribed = 'unsubscribed';
    case Bounced = 'bounced';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isMailable(): bool
    {
        return $this === self::Subscribed;
    }
}
