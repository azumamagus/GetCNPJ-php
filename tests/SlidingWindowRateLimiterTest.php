<?php

declare(strict_types=1);

namespace GetCNPJ\Tests;

use GetCNPJ\RateLimiter\SlidingWindowRateLimiter;
use PHPUnit\Framework\TestCase;

final class SlidingWindowRateLimiterTest extends TestCase
{
    public function testTracksRequestsIndependentlyPerProvider(): void
    {
        $now = 1000.0;
        $limiter = new SlidingWindowRateLimiter(2, 60, static fn (): float => $now);
        $limiter->recordRequest('A');
        $limiter->recordRequest('A');
        $limiter->recordRequest('B');

        self::assertSame(0, $limiter->getAvailableRequests('A'));
        self::assertSame(1, $limiter->getAvailableRequests('B'));
        self::assertSame(2, $limiter->getAvailableRequests('C'));
        $limiter->reset('A');
        self::assertSame(2, $limiter->getAvailableRequests('A'));
    }
}
