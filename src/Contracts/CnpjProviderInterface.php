<?php

declare(strict_types=1);

namespace GetCNPJ\Contracts;

use GetCNPJ\Models\CnpjData;

interface CnpjProviderInterface
{
    public function getProviderName(): string;

    public function getPriority(): int;

    public function getCnpjData(string $cnpj): ?CnpjData;

    public function isAvailable(): bool;
}
