<?php

namespace CreditSystem\security;

if (!defined('ABSPATH')) {
    exit;
}

class RateLimiter
{
    protected string $key;
    protected int $maxAttempts;
    protected int $decaySeconds;

    public function __construct(string $key, int $maxAttempts, int $decaySeconds)
    {
        $this->key = $this->sanitizeKey($key);
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
    }

    /**
     * Check if action is allowed
     */
    public function allow(): bool
    {
        $data = $this->getData();

        if ($data['count'] >= $this->maxAttempts) {
            return false;
        }

        $this->hit($data);

        return true;
    }

    /**
     * Remaining attempts
     */
    public function remaining(): int
    {
        $data = $this->getData();
        return max(0, $this->maxAttempts - $data['count']);
    }

    /**
     * Seconds until reset
     */
    public function retryAfter(): int
    {
        $data = $this->getData();

        if (!$data['expires_at']) {
            return 0;
        }

        return max(0, $data['expires_at'] - time());
    }

    /**
     * Increment hit counter
     */
    protected function hit(array $data): void
    {
        $data['count']++;

        update_option(
            $this->optionKey(),
            [
                'count' => $data['count'],
                'expires_at' => $data['expires_at'],
            ],
            false
        );
    }

    /**
     * Fetch limiter data
     */
    protected function getData(): array
    {
        $stored = get_option($this->optionKey());

        if (!$stored || time() > (int)$stored['expires_at']) {
            return $this->reset();
        }

        return [
            'count' => (int)$stored['count'],
            'expires_at' => (int)$stored['expires_at'],
        ];
    }

    /**
     * Reset limiter window
     */
    protected function reset(): array
    {
        $data = [
            'count' => 0,
            'expires_at' => time() + $this->decaySeconds,
        ];

        update_option($this->optionKey(), $data, false);

        return $data;
    }

    /**
     * Generate option key
     */
    protected function optionKey(): string
    {
        return 'credit_rate_limit_' . $this->key;
    }

    /**
     * Sanitize key to avoid option injection
     */
    protected function sanitizeKey(string $key): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
    }

    /**
     * Static helper for quick checks
     */
    public static function check(string $key, int $maxAttempts, int $decaySeconds): self
    {
        return new self($key, $maxAttempts, $decaySeconds);
    }
}