<?php

namespace App\Models;

// Carbon\Carbon rather than Illuminate\Support\Carbon: the framework class
// extends it, so typing on the base accepts a date from either source and the
// service does not have to care which one it was handed.
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A written horoscope for one sign, one language and one period.
 *
 * HoroscopeService prefers a row from here over its static pool, which is what
 * makes twelve sign pages twelve genuinely different pages rather than one
 * template with the name swapped.
 */
class HoroscopeReading extends Model
{
    use HasFactory;

    public const PERIOD_DAILY = 'daily';

    public const PERIOD_WEEKLY = 'weekly';

    public const PERIOD_MONTHLY = 'monthly';

    public const SOURCE_AI = 'ai';

    public const SOURCE_MANUAL = 'manual';

    protected $fillable = [
        'sign',
        'locale',
        'period',
        'period_start',
        'overview',
        'love',
        'career',
        'health',
        'money',
        'mantra',
        'lucky_number',
        'lucky_color',
        'lucky_time',
        'lucky_direction',
        'mood',
        'score',
        'scores',
        'source',
        'model',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'scores' => 'array',
            'lucky_number' => 'integer',
            'score' => 'integer',
        ];
    }

    /**
     * The first day of the period a date falls in.
     *
     * Weeks start on Monday because that is what an Indian reader means by
     * "this week", and because a weekly reading published on Sunday evening
     * should belong to the week it describes rather than the one ending.
     */
    public static function periodStart(string $period, Carbon $date): Carbon
    {
        return match ($period) {
            self::PERIOD_WEEKLY => $date->copy()->startOfWeek(Carbon::MONDAY),
            self::PERIOD_MONTHLY => $date->copy()->startOfMonth(),
            default => $date->copy()->startOfDay(),
        };
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeFor(Builder $query, string $sign, string $locale, string $period, Carbon $date): Builder
    {
        return $query->where('sign', $sign)
            ->where('locale', $locale)
            ->where('period', $period)
            ->whereDate('period_start', self::periodStart($period, $date));
    }

    /**
     * An editor's rewrite outranks the generator and must survive the next run.
     */
    public function isEditable(): bool
    {
        return $this->source === self::SOURCE_AI;
    }
}
