<?php

namespace App\Enums;

enum ContactMessageStatus: string
{
    case New = 'new';
    case Read = 'read';
    case Replied = 'replied';
    case Spam = 'spam';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
