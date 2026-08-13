<?php

namespace App\Services\Trending;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Rules\ValidTimeList;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Works out when an automatically generated article should go live.
 *
 * Dumping ten articles at once is exactly the pattern Google's scaled-content
 * policy looks for, and it buries nine of them in the feed anyway. Posts are
 * dripped instead: inside a daylight window, spaced by a minimum gap, capped
 * per day.
 */
class PublishWindow
{
    /**
     * Slots for a whole run.
     *
     * Each one is reserved as it is handed out, because the posts it is meant
     * for do not exist yet - without that, every article in a run would be
     * scheduled for the same minute.
     *
     * @return array<int, Carbon>
     */
    public function nextSlots(int $count): array
    {
        $slots = [];

        for ($i = 0; $i < $count; $i++) {
            $slot = $this->nextSlot($slots);

            if (! $slot) {
                break;
            }

            $slots[] = $slot;
        }

        return $slots;
    }

    /**
     * The next free slot, or null when the next fortnight is already full -
     * which means the caller should not be generating more content at all.
     *
     * @param  array<int, Carbon>  $reserved  slots handed out earlier in this run
     */
    public function nextSlot(array $reserved = []): ?Carbon
    {
        $times = ValidTimeList::parse((string) config('trending.publishing.slots'));

        return $times === []
            ? $this->nextEvenlySpacedSlot($reserved)
            : $this->nextNamedSlot($times, $reserved);
    }

    /**
     * The next unused one of the exact times the admin listed.
     *
     * Preferred over even spacing when set, because "publish at 08:00, 13:00
     * and 19:00" is a decision about when an audience is reading, and no
     * amount of automatic spacing can guess that.
     *
     * @param  array<int, string>  $times  HH:MM, already sorted
     * @param  array<int, Carbon>  $reserved
     */
    private function nextNamedSlot(array $times, array $reserved): ?Carbon
    {
        $config = (array) config('trending.publishing', []);
        $maxPerDay = max(1, (int) ($config['max_per_day'] ?? 8));
        $earliest = now()->addMinutes(max(0, (int) ($config['lead_minutes'] ?? 15)));

        for ($offset = 0; $offset <= 14; $offset++) {
            $date = today()->addDays($offset);

            $taken = $this->slotsOn($date)->concat(
                collect($reserved)->filter(fn (Carbon $slot) => $slot->isSameDay($date))
            );

            if ($taken->count() >= $maxPerDay) {
                continue;
            }

            foreach ($times as $time) {
                [$hour, $minute] = array_map('intval', explode(':', $time));
                $candidate = $date->copy()->setTime($hour, $minute);

                if ($candidate->lessThan($earliest)) {
                    continue;
                }

                // A time already used that day is skipped rather than doubled
                // up, so two articles never land on the same minute.
                $clash = $taken->contains(fn (Carbon $slot) => $slot->format('H:i') === $time);

                if (! $clash) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, Carbon>  $reserved
     */
    private function nextEvenlySpacedSlot(array $reserved = []): ?Carbon
    {
        $config = (array) config('trending.publishing', []);

        $gap = max(1, (int) ($config['gap_minutes'] ?? 90));
        $maxPerDay = max(1, (int) ($config['max_per_day'] ?? 8));
        $lead = max(0, (int) ($config['lead_minutes'] ?? 15));

        $earliest = now()->addMinutes($lead);

        for ($offset = 0; $offset <= 14; $offset++) {
            $date = today()->addDays($offset);

            $opens = $this->timeOn($date, (string) ($config['window_start'] ?? '07:00'), '07:00');
            $closes = $this->timeOn($date, (string) ($config['window_end'] ?? '22:00'), '22:00');

            if ($closes->lessThanOrEqualTo($opens)) {
                continue;
            }

            $taken = $this->slotsOn($date)->concat(
                collect($reserved)->filter(fn (Carbon $slot) => $slot->isSameDay($date))
            );

            if ($taken->count() >= $maxPerDay) {
                continue;
            }

            $cursor = $earliest->greaterThan($opens) ? $earliest->copy() : $opens->copy();

            // Respect the gap after whatever is already going out that day,
            // including posts published by hand.
            $last = $taken->max();

            if ($last && $last->copy()->addMinutes($gap)->greaterThan($cursor)) {
                $cursor = $last->copy()->addMinutes($gap);
            }

            if ($cursor->lessThanOrEqualTo($closes)) {
                return $cursor;
            }
        }

        return null;
    }

    /**
     * Publication times already committed for a date, from both scheduled and
     * published posts.
     *
     * @return Collection<int, Carbon>
     */
    private function slotsOn(Carbon $date): Collection
    {
        return Post::query()
            ->whereIn('status', [PostStatus::Published, PostStatus::Scheduled])
            ->where(fn ($query) => $query
                ->whereDate('published_at', $date)
                ->orWhereDate('scheduled_at', $date))
            ->get(['published_at', 'scheduled_at'])
            ->map(fn (Post $post) => $post->scheduled_at ?? $post->published_at)
            ->filter()
            ->values();
    }

    private function timeOn(Carbon $date, string $time, string $fallback): Carbon
    {
        if (! preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $time)) {
            $time = $fallback;
        }

        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $date->copy()->setTime($hour, $minute);
    }
}
