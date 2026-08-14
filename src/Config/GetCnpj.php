<?php

declare(strict_types=1);

namespace GetCNPJ\Config;

use CodeIgniter\Config\BaseConfig;

final class GetCnpj extends BaseConfig
{
    public float $timeout = 30.0;

    public int $maxRequestsPerMinute = 3;

    public bool $enableCnpjWs = true;

    public bool $enableReceitaWs = true;

    public bool $enableBrasilApi = true;

    public bool $enableCnpja = true;
}
