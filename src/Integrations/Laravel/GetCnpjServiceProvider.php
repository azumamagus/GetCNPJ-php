<?php

declare(strict_types=1);

namespace GetCNPJ\Integrations\Laravel;

use GetCNPJ\CnpjClient;
use GetCNPJ\CnpjClientOptions;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Client\ClientInterface;

final class GetCnpjServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 3) . '/config/getcnpj.php', 'getcnpj');

        $this->app->singleton(CnpjClient::class, static function (Application $app): CnpjClient {
            /** @var array<string, mixed> $config */
            $config = $app->make('config')->get('getcnpj', []);
            $providers = is_array($config['providers'] ?? null) ? $config['providers'] : [];
            $options = new CnpjClientOptions(
                timeout: (float) ($config['timeout'] ?? 30),
                maxRequestsPerMinute: (int) ($config['max_requests_per_minute'] ?? 3),
                enableCnpjWs: (bool) ($providers['cnpj_ws'] ?? true),
                enableReceitaWs: (bool) ($providers['receita_ws'] ?? true),
                enableBrasilApi: (bool) ($providers['brasil_api'] ?? true),
                enableCnpja: (bool) ($providers['cnpja'] ?? true),
            );
            $httpClient = $app->bound(ClientInterface::class) ? $app->make(ClientInterface::class) : null;

            return new CnpjClient($httpClient, $options);
        });

        $this->app->alias(CnpjClient::class, 'getcnpj');
    }

    public function boot(): void
    {
        $this->publishes([
            dirname(__DIR__, 3) . '/config/getcnpj.php' => $this->app->configPath('getcnpj.php'),
        ], 'getcnpj-config');
    }
}
