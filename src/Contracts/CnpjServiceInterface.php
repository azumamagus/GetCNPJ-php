<?php

declare(strict_types=1);

namespace GetCNPJ\Contracts;

use GetCNPJ\Models\CnpjResult;

interface CnpjServiceInterface
{
    public function getCnpj(string $cnpj): CnpjResult;

    public function getCnpjFromProvider(string $cnpj, string $providerName): CnpjResult;
}
