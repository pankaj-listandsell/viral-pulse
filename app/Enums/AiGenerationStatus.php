<?php

namespace App\Enums;

enum AiGenerationStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::Rejected], true);
    }
}
