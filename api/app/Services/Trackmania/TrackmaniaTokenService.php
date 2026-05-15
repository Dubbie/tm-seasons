<?php

namespace App\Services\Trackmania;

use App\Exceptions\Trackmania\TrackmaniaTokenException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TrackmaniaTokenService
{
    private const TOKEN_CACHE_KEY_DEFAULT = 'trackmania.token';
    private const TOKEN_CACHE_TTL_FALLBACK_DEFAULT = 900;
    private const TOKEN_EXPIRY_SKEW_SECONDS_DEFAULT = 60;
    private const RETRY_TIMES_DEFAULT = 3;
    private const RETRY_SLEEP_MS_DEFAULT = 200;
    private const TIMEOUT_SECONDS_DEFAULT = 10;
    private const AUDIENCE_DEFAULT = 'NadeoLiveServices';
    private const USER_AGENT_DEFAULT = 'tm-bot/1.0 (+https://example.com)';

    public function getToken(?string $audience = null): string
    {
        $audience = $audience ?: (string) config('trackmania.audience', self::AUDIENCE_DEFAULT);
        $cacheKey = $this->cacheKey($audience);

        return Cache::remember($cacheKey, $this->cacheTtlSeconds(), function () use ($audience): string {
            $response = $this->request()
                ->post($this->tokenEndpoint(), ['audience' => $audience]);

            if (! $response->successful()) {
                $snippet = mb_substr((string) $response->body(), 0, 240);
                throw new TrackmaniaTokenException(sprintf(
                    'Failed to retrieve Trackmania token (status %d, method basic). Response: %s',
                    $response->status(),
                    $snippet === '' ? '[empty]' : $snippet,
                ));
            }

            $payload = $response->json();

            if (! is_array($payload)) {
                throw new TrackmaniaTokenException('Trackmania token response was not valid JSON.');
            }

            $token = $payload['accessToken'] ?? $payload['access_token'] ?? null;

            if (! is_string($token) || $token === '') {
                throw new TrackmaniaTokenException('Trackmania token response did not include accessToken.');
            }

            return $token;
        });
    }

    private function request(): PendingRequest
    {
        return Http::retry(
            (int) config('trackmania.retry_times', self::RETRY_TIMES_DEFAULT),
            (int) config('trackmania.retry_sleep_ms', self::RETRY_SLEEP_MS_DEFAULT),
            throw: false,
        )
            ->timeout((int) config('trackmania.timeout_seconds', self::TIMEOUT_SECONDS_DEFAULT))
            ->acceptJson()
            ->withUserAgent((string) config('trackmania.user_agent', self::USER_AGENT_DEFAULT))
            ->withBasicAuth(
                (string) config('trackmania.dedicated_login', ''),
                (string) config('trackmania.dedicated_password', ''),
            );
    }

    private function tokenEndpoint(): string
    {
        $baseUrl = rtrim((string) config('trackmania.auth_base_url'), '/');

        return sprintf('%s/v2/authentication/token/basic', $baseUrl);
    }

    private function cacheKey(string $audience): string
    {
        return sprintf('%s.%s', (string) config('trackmania.token_cache_key', self::TOKEN_CACHE_KEY_DEFAULT), $audience);
    }

    private function cacheTtlSeconds(): int
    {
        return max(
            1,
            (int) config('trackmania.token_cache_ttl_fallback', self::TOKEN_CACHE_TTL_FALLBACK_DEFAULT)
            - (int) config('trackmania.token_expiry_skew_seconds', self::TOKEN_EXPIRY_SKEW_SECONDS_DEFAULT)
        );
    }

    private function ttlFromPayload(array $payload): int
    {
        $fallback = $this->cacheTtlSeconds();
        $skew = (int) config('trackmania.token_expiry_skew_seconds', self::TOKEN_EXPIRY_SKEW_SECONDS_DEFAULT);

        $expiresIn = $payload['expiresIn'] ?? $payload['expires_in'] ?? null;
        if (is_numeric($expiresIn)) {
            return max(1, (int) $expiresIn - $skew);
        }

        $token = $payload['accessToken'] ?? $payload['access_token'] ?? null;
        if (! is_string($token) || substr_count($token, '.') !== 2) {
            return $fallback;
        }

        $decoded = $this->decodeJwtPayload($token);

        if (! is_array($decoded) || ! is_numeric($decoded['exp'] ?? null)) {
            return $fallback;
        }

        return max(1, ((int) $decoded['exp']) - now()->timestamp - $skew);
    }

    private function decodeJwtPayload(string $token): ?array
    {
        $parts = explode('.', $token);
        if (! isset($parts[1])) {
            return null;
        }

        $payload = strtr($parts[1], '-_', '+/');
        $padding = strlen($payload) % 4;
        if ($padding !== 0) {
            $payload .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            return null;
        }

        $json = json_decode($decoded, true);

        return is_array($json) ? $json : null;
    }
}
