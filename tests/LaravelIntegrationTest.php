<?php

declare(strict_types=1);

namespace GetCNPJ\Tests;

use GetCNPJ\CnpjClient;
use GetCNPJ\Integrations\Laravel\Facades\GetCNPJ as GetCnpjFacade;
use GetCNPJ\Integrations\Laravel\GetCnpjServiceProvider;
use Orchestra\Testbench\TestCase;

final class LaravelIntegrationTest extends TestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [GetCnpjServiceProvider::class];
    }

    public function testLaravelResolvesSharedClientAndFacade(): void
    {
        $client = $this->app->make(CnpjClient::class);

        self::assertInstanceOf(CnpjClient::class, $client);
        self::assertSame($client, $this->app->make(CnpjClient::class));
        self::assertSame($client, $this->app->make('getcnpj'));
        self::assertSame($client, GetCnpjFacade::getFacadeRoot());
    }

    public function testLaravelLoadsDefaultPackageConfiguration(): void
    {
        self::assertSame(30.0, config('getcnpj.timeout'));
        self::assertSame(3, config('getcnpj.max_requests_per_minute'));
        self::assertTrue(config('getcnpj.providers.cnpj_ws'));
    }
}
