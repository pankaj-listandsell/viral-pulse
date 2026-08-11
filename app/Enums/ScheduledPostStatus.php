<?php

namespace App\Enums;

enum ScheduledPostStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Published = 'published';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
