<?php

namespace App\Enums;

enum PostSourceType: string
{
    case Manual = 'manual';
    case Ai = 'ai';
    case Trending = 'trending';
    case Imported = 'imported';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Written manually',
            self::Ai => 'AI generated',
            self::Trending => 'From trending topic',
            self::Imported => 'Imported',
        };
    }
}
