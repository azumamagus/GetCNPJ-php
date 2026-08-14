<?php

declare(strict_types=1);

namespace GetCNPJ\Tests;

use GetCNPJ\CnpjClient;
use GetCNPJ\Config\GetCnpj;
use GetCNPJ\Config\Services;
use PHPUnit\Framework\TestCase;

final class CodeIgniterIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Services::reset(false);
        parent::tearDown();
    }

    public function testCodeIgniterCreatesClientFromPackageConfiguration(): void
    {
        $config = new GetCnpj();
        $client = Services::getCnpj(false);

        self::assertInstanceOf(GetCnpj::class, $config);
        self::assertSame(30.0, $config->timeout);
        self::assertInstanceOf(CnpjClient::class, $client);
    }

    public function testCodeIgniterReturnsSharedService(): void
    {
        self::assertSame(Services::getCnpj(), Services::getCnpj());
    }
}
