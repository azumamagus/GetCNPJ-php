<?php

declare(strict_types=1);

namespace GetCNPJ;

use InvalidArgumentException;

final class CnpjClientOptions
{
    public function __construct(
        public readonly float $timeout = 30.0,
        public readonly int $maxRequestsPerMinute = 3,
        public readonly bool $enableCnpjWs = true,
        public readonly bool $enableReceitaWs = true,
        public readonly bool $enableBrasilApi = true,
        public readonly bool $enableCnpja = true,
    ) {
        if ($timeout <= 0) {
            throw new InvalidArgumentException('timeout deve ser maior que zero.');
        }
        if ($maxRequestsPerMinute < 1) {
            throw new InvalidArgumentException('maxRequestsPerMinute deve ser maior que zero.');
        }
        if (!$enableCnpjWs && !$enableReceitaWs && !$enableBrasilApi && !$enableCnpja) {
            throw new InvalidArgumentException('Pelo menos um provedor deve estar habilitado.');
        }
    }
}
