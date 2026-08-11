<?php

namespace App\Enums;

enum PostStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Scheduled => 'amber',
            self::Published => 'green',
            self::Archived => 'slate',
        };
    }

    public function isPubliclyVisible(): bool
    {
        return $this === self::Published;
    }
}
