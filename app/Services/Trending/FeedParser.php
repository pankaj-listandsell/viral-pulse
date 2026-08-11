<?php

namespace App\Services\Trending;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

/**
 * Turns RSS 2.0, Atom and Google Trends XML into a single flat item shape.
 *
 * Feeds are third-party input, so nothing here trusts the document: entity
 * loading and network access are off, malformed XML returns an empty list
 * rather than raising, and every field is treated as optional.
 */
class FeedParser
{
    /**
     * @return array<int, array{title: string, description: ?string, link: ?string, published_at: ?Carbon, volume: ?int}>
     */
    public function parse(string $xml, ?string $context = null): array
    {
        $xml = trim($xml);

        if ($xml === '') {
            return [];
        }

        $previous = libxml_use_internal_errors(true);

        try {
            // LIBXML_NONET blocks external fetches; LIBXML_NOENT is deliberately
            // NOT passed - it would substitute entities, which is the XXE foot-gun.
            $document = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($document === false) {
            Log::warning('Trending feed could not be parsed as XML', ['source' => $context]);

            return [];
        }

        $items = $this->locateItems($document);
        $parsed = [];

        foreach ($items as $item) {
            $entry = $this->parseItem($item);

            if ($entry !== null) {
                $parsed[] = $entry;
            }
        }

        return $parsed;
    }

    /**
     * @return array<int, SimpleXMLElement>
     */
    private function locateItems(SimpleXMLElement $document): array
    {
        // RSS 2.0
        if (isset($document->channel->item)) {
            return iterator_to_array($document->channel->item, false);
        }

        // RSS 1.0 / RDF puts items as siblings of the channel.
        if (isset($document->item)) {
            return iterator_to_array($document->item, false);
        }

        // Atom
        if (isset($document->entry)) {
            return iterator_to_array($document->entry, false);
        }

        return [];
    }

    /**
     * @return array{title: string, description: ?string, link: ?string, published_at: ?Carbon, volume: ?int}|null
     */
    private function parseItem(SimpleXMLElement $item): ?array
    {
        $title = $this->cleanText((string) ($item->title ?? ''));

        if ($title === '') {
            return null;
        }

        return [
            'title' => $title,
            'description' => $this->description($item),
            'link' => $this->link($item),
            'published_at' => $this->publishedAt($item),
            'volume' => $this->approximateTraffic($item),
        ];
    }

    private function description(SimpleXMLElement $item): ?string
    {
        $candidates = [
            (string) ($item->description ?? ''),
            (string) ($item->summary ?? ''),
            (string) ($item->content ?? ''),
        ];

        // Google Trends carries the actual context in a namespaced child, so the
        // first news snippet is a far better description than the markup blob
        // in <description>.
        $snippet = $this->namespacedValue($item, 'news_item_snippet');

        if ($snippet !== null) {
            array_unshift($candidates, $snippet);
        }

        foreach ($candidates as $candidate) {
            $text = $this->cleanText($candidate);

            if ($text !== '') {
                return mb_substr($text, 0, 1000);
            }
        }

        return null;
    }

    private function link(SimpleXMLElement $item): ?string
    {
        $link = trim((string) ($item->link ?? ''));

        // Atom puts the URL in an attribute rather than the node body.
        if ($link === '' && isset($item->link['href'])) {
            $link = trim((string) $item->link['href']);
        }

        if ($link === '') {
            $link = $this->namespacedValue($item, 'news_item_url') ?? '';
        }

        if ($link === '' || ! filter_var($link, FILTER_VALIDATE_URL)) {
            return null;
        }

        return mb_substr($link, 0, 2000);
    }

    private function publishedAt(SimpleXMLElement $item): ?Carbon
    {
        foreach (['pubDate', 'published', 'updated', 'date'] as $field) {
            $value = trim((string) ($item->{$field} ?? ''));

            if ($value === '') {
                continue;
            }

            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                // A feed with an unparseable date is common enough that it is
                // not worth a log line; the caller falls back to "now".
                continue;
            }
        }

        return null;
    }

    /**
     * Google Trends publishes "20,000+" style volumes. It is approximate by
     * definition, but it is the only real demand signal in the pipeline.
     */
    private function approximateTraffic(SimpleXMLElement $item): ?int
    {
        $value = $this->namespacedValue($item, 'approx_traffic');

        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $value) ?? '';

        return $digits === '' ? null : (int) $digits;
    }

    private function namespacedValue(SimpleXMLElement $item, string $field): ?string
    {
        foreach ($item->getNamespaces(true) as $namespace) {
            $children = $item->children($namespace);

            if (isset($children->{$field})) {
                $value = $this->cleanText((string) $children->{$field});

                if ($value !== '') {
                    return $value;
                }
            }

            // Google Trends nests the snippet and url one level deeper.
            if (isset($children->news_item->{$field})) {
                $value = $this->cleanText((string) $children->news_item->{$field});

                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function cleanText(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return trim($value);
    }
}
