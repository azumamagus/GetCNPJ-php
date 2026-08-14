<?php

declare(strict_types=1);

namespace GetCNPJ\Contracts;

interface RateLimiterInterface
{
    public function waitIfNeeded(string $providerName): void;

    public function recordRequest(string $providerName): void;

    public function reset(string $providerName): void;

    public function getAvailableRequests(string $providerName): int;
}
