<?php

namespace App\Services\Trackmania;

use App\Exceptions\Trackmania\TrackmaniaTokenException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TrackmaniaTokenService
{
    public function getToken(?string $audience = null): string
    {
        $audience = $audience ?: (string) config('trackmania.audience', 'NadeoLiveServices');
        $cacheKey = $this->cacheKey($audience);

        return Cache::remember($cacheKey, $this->cacheTtlSeconds(), function () use ($audience): string {
            $response = Http::retry(
                (int) config('trackmania.retry_times', 3),
                (int) config('trackmania.retry_sleep_ms', 200),
                throw: false,
            )
                ->timeout((int) config('trackmania.timeout_seconds', 10))
                ->acceptJson()
                ->withBasicAuth(
                    (string) config('trackmania.dedicated_login', ''),
                    (string) config('trackmania.dedicated_password', ''),
                )
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

            $ttl = $this->ttlFromPayload($payload);
            Cache::put($this->cacheKey($audience), $token, $ttl);

            return $token;
        });
    }

    private function tokenEndpoint(): string
    {
        $baseUrl = rtrim((string) config('trackmania.auth_base_url'), '/');

        return sprintf('%s/v2/authentication/token/basic', $baseUrl);
    }

    private function cacheKey(string $audience): string
    {
        return sprintf('%s.%s', (string) config('trackmania.token_cache_key', 'trackmania.token'), $audience);
    }

    private function cacheTtlSeconds(): int
    {
        return max(1, (int) config('trackmania.token_cache_ttl_fallback', 900) - (int) config('trackmania.token_expiry_skew_seconds', 60));
    }

    private function ttlFromPayload(array $payload): int
    {
        $fallback = $this->cacheTtlSeconds();
        $skew = (int) config('trackmania.token_expiry_skew_seconds', 60);

        $expiresIn = $payload['expiresIn'] ?? $payload['expires_in'] ?? null;
        if (is_numeric($expiresIn)) {
            return max(1, (int) $expiresIn - $skew);
        }

        $token = $payload['accessToken'] ?? $payload['access_token'] ?? null;
        if (! is_string($token) || substr_count($token, '.') !== 2) {
            return $fallback;
        }

        $parts = explode('.', $token);
        $decoded = json_decode(base64_decode(strtr($parts[1], '-_', '+/'), true) ?: '', true);

        if (! is_array($decoded) || ! is_numeric($decoded['exp'] ?? null)) {
            return $fallback;
        }

        return max(1, ((int) $decoded['exp']) - now()->timestamp - $skew);
    }
}
