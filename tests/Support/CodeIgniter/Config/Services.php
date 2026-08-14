<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseService;
use GetCNPJ\CnpjClient;

final class Services extends BaseService
{
    public static function getCnpj(bool $getShared = true): CnpjClient
    {
        return \GetCNPJ\Config\Services::getCnpj($getShared);
    }
}
