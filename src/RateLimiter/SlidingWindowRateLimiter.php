<?php

declare(strict_types=1);

namespace GetCNPJ\RateLimiter;

use GetCNPJ\Contracts\RateLimiterInterface;
use InvalidArgumentException;

final class SlidingWindowRateLimiter implements RateLimiterInterface
{
    /** @var array<string, list<float>> */
    private array $requestTimestamps = [];

    /** @param null|callable():float $clock @param null|callable(int):void $sleeper */
    public function __construct(
        private readonly int $maxRequests = 3,
        private readonly float $timeWindowSeconds = 60.0,
        private readonly mixed $clock = null,
        private readonly mixed $sleeper = null,
    ) {
        if ($maxRequests < 1) {
            throw new InvalidArgumentException('maxRequests deve ser maior que zero.');
        }
        if ($timeWindowSeconds <= 0) {
            throw new InvalidArgumentException('timeWindowSeconds deve ser maior que zero.');
        }
        if ($clock !== null && !is_callable($clock)) {
            throw new InvalidArgumentException('clock deve ser callable.');
        }
        if ($sleeper !== null && !is_callable($sleeper)) {
            throw new InvalidArgumentException('sleeper deve ser callable.');
        }
    }

    public function waitIfNeeded(string $providerName): void
    {
        $this->cleanOldTimestamps($providerName);

        while (count($this->requestTimestamps[$providerName] ?? []) >= $this->maxRequests) {
            $oldest = $this->requestTimestamps[$providerName][0];
            $waitSeconds = ($oldest + $this->timeWindowSeconds) - $this->now();
            if ($waitSeconds > 0) {
                $this->sleep((int) ceil($waitSeconds * 1_000_000));
            }
            $this->cleanOldTimestamps($providerName);
        }
    }

    public function recordRequest(string $providerName): void
    {
        $this->requestTimestamps[$providerName][] = $this->now();
    }

    public function reset(string $providerName): void
    {
        unset($this->requestTimestamps[$providerName]);
    }

    public function getAvailableRequests(string $providerName): int
    {
        $this->cleanOldTimestamps($providerName);

        return max(0, $this->maxRequests - count($this->requestTimestamps[$providerName] ?? []));
    }

    private function cleanOldTimestamps(string $providerName): void
    {
        $cutoff = $this->now() - $this->timeWindowSeconds;
        $this->requestTimestamps[$providerName] = array_values(array_filter(
            $this->requestTimestamps[$providerName] ?? [],
            static fn (float $timestamp): bool => $timestamp >= $cutoff,
        ));
    }

    private function now(): float
    {
        return $this->clock !== null ? ($this->clock)() : microtime(true);
    }

    private function sleep(int $microseconds): void
    {
        if ($this->sleeper !== null) {
            ($this->sleeper)($microseconds);
            return;
        }

        usleep($microseconds);
    }
}
