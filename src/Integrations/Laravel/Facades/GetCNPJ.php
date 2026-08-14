<?php

declare(strict_types=1);

namespace GetCNPJ\Integrations\Laravel\Facades;

use GetCNPJ\CnpjClient;
use GetCNPJ\Models\CnpjResult;
use GetCNPJ\ProviderType;
use Illuminate\Support\Facades\Facade;

/**
 * @method static CnpjResult get(string $cnpj)
 * @method static CnpjResult getFromProvider(string $cnpj, ProviderType|string $provider)
 *
 * @see CnpjClient
 */
final class GetCNPJ extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CnpjClient::class;
    }
}
