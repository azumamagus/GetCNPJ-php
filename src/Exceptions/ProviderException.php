<?php

declare(strict_types=1);

namespace GetCNPJ\Exceptions;

final class ProviderException extends CnpjException
{
    public function __construct(
        public readonly string $providerName,
        string $message,
        ?\Throwable $previous = null,
    ) {
        parent::__construct("[{$providerName}] {$message}", 0, $previous);
    }
}
