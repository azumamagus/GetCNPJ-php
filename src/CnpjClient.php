<?php

declare(strict_types=1);

namespace GetCNPJ;

use GetCNPJ\Contracts\CnpjServiceInterface;
use GetCNPJ\Contracts\RateLimiterInterface;
use GetCNPJ\Models\CnpjResult;
use GetCNPJ\Providers\BrasilApiProvider;
use GetCNPJ\Providers\CnpjaProvider;
use GetCNPJ\Providers\CnpjWsProvider;
use GetCNPJ\Providers\ReceitaWsProvider;
use GetCNPJ\RateLimiter\SlidingWindowRateLimiter;
use GetCNPJ\Services\CnpjService;
use GuzzleHttp\Client;
use Psr\Http\Client\ClientInterface;

final class CnpjClient
{
    private readonly CnpjServiceInterface $cnpjService;

    public function __construct(
        ?ClientInterface $httpClient = null,
        ?CnpjClientOptions $options = null,
        ?RateLimiterInterface $rateLimiter = null,
    ) {
        $options ??= new CnpjClientOptions();
        $httpClient ??= new Client(['timeout' => $options->timeout, 'http_errors' => false]);
        $rateLimiter ??= new SlidingWindowRateLimiter($options->maxRequestsPerMinute);

        $providers = [];
        if ($options->enableCnpjWs) {
            $providers[] = new CnpjWsProvider($httpClient, $rateLimiter);
        }
        if ($options->enableReceitaWs) {
            $providers[] = new ReceitaWsProvider($httpClient, $rateLimiter);
        }
        if ($options->enableBrasilApi) {
            $providers[] = new BrasilApiProvider($httpClient, $rateLimiter);
        }
        if ($options->enableCnpja) {
            $providers[] = new CnpjaProvider($httpClient, $rateLimiter);
        }
        $this->cnpjService = new CnpjService($providers);
    }

    public function get(string $cnpj): CnpjResult
    {
        return $this->cnpjService->getCnpj($cnpj);
    }

    public function getFromProvider(string $cnpj, ProviderType|string $provider): CnpjResult
    {
        $providerName = $provider instanceof ProviderType ? $provider->value : $provider;
        return $this->cnpjService->getCnpjFromProvider($cnpj, $providerName);
    }
}
