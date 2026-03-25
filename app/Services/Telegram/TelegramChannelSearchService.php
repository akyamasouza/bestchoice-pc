<?php

namespace App\Services\Telegram;

use danog\MadelineProto\API;
use danog\MadelineProto\Logger as MadelineLogger;
use danog\MadelineProto\Settings;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class TelegramChannelSearchService
{
    private ?API $client = null;

    public function searchChannel(string $query, ?string $channel = null, int $limit = 10): Collection
    {
        $channel = $channel ?: (string) config('services.telegram.default_channel');

        if (blank($channel)) {
            throw new RuntimeException('Configure TELEGRAM_DEFAULT_CHANNEL before searching.');
        }

        $result = $this->client()->messages->search(
            peer: $channel,
            q: $query,
            filter: ['_' => 'inputMessagesFilterEmpty'],
            min_date: 0,
            max_date: 0,
            offset_id: 0,
            add_offset: 0,
            limit: $limit,
            max_id: 0,
            min_id: 0,
            hash: 0,
        );

        return collect(Arr::get($result, 'messages', []))
            ->map(fn (array $message): array => $this->normalizeMessage($message, $channel))
            ->filter(fn (array $message): bool => filled($message['text']))
            ->values();
    }

    private function client(): API
    {
        if ($this->client instanceof API) {
            return $this->client;
        }

        $apiId = config('services.telegram.api_id');
        $apiHash = config('services.telegram.api_hash');

        if (blank($apiId) || blank($apiHash)) {
            throw new RuntimeException('Configure TELEGRAM_API_ID and TELEGRAM_API_HASH before using the Telegram API.');
        }

        $settings = new Settings;
        $settings->getAppInfo()
            ->setApiId((int) $apiId)
            ->setApiHash((string) $apiHash);
        $settings->getLogger()->setLevel(MadelineLogger::LEVEL_ERROR);

        $sessionPath = (string) config('services.telegram.session_path');

        if (blank($sessionPath)) {
            throw new RuntimeException('Configure TELEGRAM_SESSION_PATH before using the Telegram API.');
        }

        File::ensureDirectoryExists(dirname($sessionPath));

        $this->client = new API($sessionPath, $settings);
        $this->client->start();

        return $this->client;
    }

    private function normalizeMessage(array $message, string $channel): array
    {
        $text = trim((string) ($message['message'] ?? ''));
        $channelHandle = ltrim($channel, '@');
        $messageId = Arr::get($message, 'id');
        $date = Arr::get($message, 'date');

        return [
            'id' => $messageId,
            'date' => $date,
            'date_iso' => filled($date) ? now()->setTimestamp((int) $date)->toIso8601String() : null,
            'text' => $text,
            'excerpt' => Str::limit(preg_replace('/\s+/', ' ', $text) ?: '', 200),
            'views' => Arr::get($message, 'views'),
            'forwards' => Arr::get($message, 'forwards'),
            'url' => filled($messageId) ? "https://t.me/{$channelHandle}/{$messageId}" : null,
        ];
    }
}
