<?php

declare(strict_types=1);

namespace GetCNPJ\Models;

use DateTimeImmutable;
use JsonSerializable;

final class ProviderError implements JsonSerializable
{
    public readonly DateTimeImmutable $timestamp;

    public function __construct(
        public readonly string $providerName,
        public readonly string $errorMessage,
        public readonly ?\Throwable $exception = null,
    ) {
        $this->timestamp = new DateTimeImmutable();
    }

    public function jsonSerialize(): array
    {
        return [
            'providerName' => $this->providerName,
            'errorMessage' => $this->errorMessage,
            'timestamp' => $this->timestamp->format(DATE_ATOM),
        ];
    }
}
