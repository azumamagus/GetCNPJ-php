<?php

declare(strict_types=1);

namespace GetCNPJ\Config;

use CodeIgniter\Config\BaseService;
use GetCNPJ\CnpjClient;
use GetCNPJ\CnpjClientOptions;

final class Services extends BaseService
{
    public static function getCnpj(bool $getShared = true): CnpjClient
    {
        if ($getShared) {
            return static::getSharedInstance('getCnpj');
        }

        $config = new GetCnpj();

        return new CnpjClient(options: new CnpjClientOptions(
            timeout: $config->timeout,
            maxRequestsPerMinute: $config->maxRequestsPerMinute,
            enableCnpjWs: $config->enableCnpjWs,
            enableReceitaWs: $config->enableReceitaWs,
            enableBrasilApi: $config->enableBrasilApi,
            enableCnpja: $config->enableCnpja,
        ));
    }
}
