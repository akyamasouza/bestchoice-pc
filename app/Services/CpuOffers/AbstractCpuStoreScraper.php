<?php

namespace App\Services\CpuOffers;

use App\DataTransferObjects\ScrapedCpuOfferData;
use App\Services\CpuOffers\Contracts\CpuStoreScraper;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class AbstractCpuStoreScraper implements CpuStoreScraper
{
    final public function scrape(string $url): ScrapedCpuOfferData
    {
        $html = $this->fetchHtml($url);

        return $this->parseDocument($html, $this->visibleText($html), $url);
    }

    abstract protected function parseDocument(string $html, string $text, string $url): ScrapedCpuOfferData;

    protected function fetchHtml(string $url): string
    {
        $response = Http::withOptions([
            'verify' => ! app()->isLocal(),
        ])->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept-Encoding' => 'gzip, deflate',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
        ])->timeout(20)->retry(2, 500)->get($url);

        $this->ensureSuccessfulResponse($response, $url);

        return $response->body();
    }

    protected function ensureSuccessfulResponse(Response $response, string $url): void
    {
        if (! $response->successful()) {
            throw new RuntimeException("Failed to fetch [{$url}] with status {$response->status()}.");
        }
    }

    protected function visibleText(string $html): string
    {
        $html = preg_replace('/<!--.*?-->/s', ' ', $html) ?? $html;
        $html = preg_replace('/<script\\b[^>]*>.*?<\\/script>/is', ' ', $html) ?? $html;
        $html = preg_replace('/<style\\b[^>]*>.*?<\\/style>/is', ' ', $html) ?? $html;
        $html = str_replace('><', '> <', $html);

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\\s+/u', ' ', $text));
    }

    protected function parseMoney(string $value): float
    {
        $normalized = preg_replace('/[^\\d,\\.]/', '', $value) ?? '';

        if ($normalized === '') {
            throw new RuntimeException('Unable to parse monetary value.');
        }

        $lastComma = strrpos($normalized, ',');
        $lastDot = strrpos($normalized, '.');

        if ($lastComma !== false && $lastDot !== false) {
            $decimalSeparator = $lastComma > $lastDot ? ',' : '.';
            $thousandSeparator = $decimalSeparator === ',' ? '.' : ',';

            $normalized = str_replace($thousandSeparator, '', $normalized);
            $normalized = str_replace($decimalSeparator, '.', $normalized);
        } elseif ($lastComma !== false) {
            $normalized = preg_match('/,\\d{2}$/', $normalized)
                ? str_replace(',', '.', $normalized)
                : str_replace(',', '', $normalized);
        } elseif ($lastDot !== false && ! preg_match('/\\.\\d{2}$/', $normalized)) {
            $normalized = str_replace('.', '', $normalized);
        }

        return round((float) $normalized, 2);
    }

    protected function firstMatch(string $subject, array|string $patterns): ?array
    {
        foreach ((array) $patterns as $pattern) {
            if (preg_match($pattern, $subject, $matches)) {
                return $matches;
            }
        }

        return null;
    }

    protected function matchOrFail(string $subject, array|string $patterns, string $message): array
    {
        $matches = $this->firstMatch($subject, $patterns);

        if ($matches === null) {
            throw new RuntimeException($message);
        }

        return $matches;
    }

    protected function extractTagText(string $html, array|string $patterns): ?string
    {
        $matches = $this->firstMatch($html, $patterns);

        if ($matches === null) {
            return null;
        }

        return trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    protected function extractMetaContent(string $html, string $name): ?string
    {
        $quotedName = preg_quote($name, '/');

        return $this->extractTagText(
            $html,
            [
                '/<meta[^>]+name=["\']'.$quotedName.'["\'][^>]+content=["\']([^"\']+)["\']/iu',
                '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']'.$quotedName.'["\']/iu',
                '/<meta[^>]+property=["\']'.$quotedName.'["\'][^>]+content=["\']([^"\']+)["\']/iu',
            ],
        );
    }

    protected function normalizeSeller(string $seller, array $stopTerms = []): string
    {
        if ($stopTerms !== []) {
            $pattern = '/('.implode('|', array_map(static fn (string $term): string => preg_quote($term, '/'), $stopTerms)).').*$/u';
            $seller = preg_replace($pattern, '', $seller) ?? $seller;
        }

        $seller = preg_replace('/[^\\pL\\pN&!\\.\\-\\s]+/u', ' ', $seller) ?? $seller;

        return trim((string) preg_replace('/\\s+/u', ' ', $seller));
    }

    protected function extractTitleFromText(string $text, string $fallbackPrefix = 'Processador'): ?string
    {
        if (preg_match("/({$fallbackPrefix}[^\\r\\n]+?100-[A-Z0-9-]+)/iu", $text, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
