<?php

namespace App\Enums;

enum TrendingTopicStatus: string
{
    case New = 'new';
    case Queued = 'queued';
    case Generating = 'generating';
    case Generated = 'generated';
    case Scheduled = 'scheduled';
    case Ignored = 'ignored';
    case Failed = 'failed';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Topics in these states are still eligible to be picked up by the
     * automatic generation run.
     */
    public function isAvailableForGeneration(): bool
    {
        return in_array($this, [self::New, self::Failed], true);
    }
}
