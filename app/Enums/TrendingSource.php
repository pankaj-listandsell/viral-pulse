<?php

namespace App\Enums;

enum TrendingSource: string
{
    case Manual = 'manual';
    case Rss = 'rss';
    case GoogleTrends = 'google_trends';
    case NewsApi = 'news_api';
    case Social = 'social';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual entry',
            self::Rss => 'RSS feed',
            self::GoogleTrends => 'Google Trends',
            self::NewsApi => 'News API',
            self::Social => 'Social',
        };
    }
}
