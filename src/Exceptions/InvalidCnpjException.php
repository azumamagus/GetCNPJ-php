<?php

declare(strict_types=1);

namespace GetCNPJ\Exceptions;

final class InvalidCnpjException extends CnpjException
{
    public function __construct(public readonly string $cnpj, ?\Throwable $previous = null)
    {
        parent::__construct("CNPJ inválido: {$cnpj}", 0, $previous);
    }
}
