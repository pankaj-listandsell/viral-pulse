<?php

namespace App\Enums;

enum ContentTone: string
{
    case Informative = 'informative';
    case Conversational = 'conversational';
    case Enthusiastic = 'enthusiastic';
    case Professional = 'professional';
    case Witty = 'witty';
    case Respectful = 'respectful';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function guidance(): string
    {
        return match ($this) {
            self::Informative => 'Neutral and factual. Explain rather than persuade.',
            self::Conversational => 'Write the way you would explain it to a friend. Contractions are fine.',
            self::Enthusiastic => 'Energetic and warm, without hype or exclamation marks in every line.',
            self::Professional => 'Precise and measured. Suitable for a business audience.',
            self::Witty => 'Light and playful, but never at the expense of being clear or accurate.',
            self::Respectful => 'Careful and considerate. Appropriate for religious or sensitive subjects.',
        };
    }
}
