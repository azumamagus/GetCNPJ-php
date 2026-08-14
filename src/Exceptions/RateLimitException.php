<?php

declare(strict_types=1);

namespace GetCNPJ\Exceptions;

final class RateLimitException extends CnpjException
{
    public function __construct(
        public readonly string $providerName,
        public readonly float $waitTime,
    ) {
        parent::__construct(sprintf(
            'Rate limit excedido para o provedor %s. Aguarde %.0f segundos.',
            $providerName,
            $waitTime,
        ));
    }
}
