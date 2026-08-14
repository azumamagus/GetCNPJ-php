<?php

declare(strict_types=1);

namespace GetCNPJ\Tests;

use GetCNPJ\CnpjClient;
use GetCNPJ\CnpjClientOptions;
use GetCNPJ\Exceptions\InvalidCnpjException;
use GetCNPJ\ProviderType;
use GetCNPJ\Tests\Support\MockHttpClient;
use PHPUnit\Framework\TestCase;

final class CnpjClientTest extends TestCase
{
    public function testConsultsSpecificProviderAndMapsBrasilApiResponse(): void
    {
        $url = 'https://brasilapi.com.br/api/cnpj/v1/03312791000183';
        $http = new MockHttpClient([$url => ['body' => json_encode([
            'cnpj' => '03312791000183',
            'razao_social' => 'SOS SYSTEM LTDA',
            'nome_fantasia' => 'SOS System',
            'data_inicio_atividade' => '1999-08-12',
            'descricao_situacao_cadastral' => 'ATIVA',
            'descricao_matriz_filial' => 'MATRIZ',
            'descricao_porte' => 'MICRO EMPRESA',
            'capital_social' => 10000,
            'cep' => '01001000',
            'descricao_tipo_logradouro' => 'RUA',
            'logradouro' => 'EXEMPLO',
            'numero' => '10',
            'municipio' => 'SÃO PAULO',
            'uf' => 'SP',
            'ddd_telefone_1' => '1133334444',
            'cnae_fiscal' => 6201501,
            'cnae_fiscal_descricao' => 'Desenvolvimento de programas',
            'qsa' => [['nome_socio' => 'ANA', 'qualificacao_socio' => 'Sócio-Administrador']],
            'opcao_pelo_simples' => true,
        ], JSON_THROW_ON_ERROR)]]);
        $options = new CnpjClientOptions(enableCnpjWs: false, enableReceitaWs: false, enableCnpja: false);
        $result = (new CnpjClient($http, $options))->getFromProvider('03.312.791/0001-83', ProviderType::BrasilAPI);

        self::assertTrue($result->success);
        self::assertSame('SOS SYSTEM LTDA', $result->data?->razaoSocial);
        self::assertSame('03.312.791/0001-83', $result->data?->cnpj);
        self::assertSame('BrasilAPI', $result->data?->provedor);
        self::assertSame('(11) 3333-4444', $result->data?->telefones[0]);
        self::assertSame('62.01-5-01', $result->data?->atividadePrincipal?->codigo);
        self::assertSame('01001-000', $result->data?->endereco?->cep);
    }

    public function testFallsBackToNextProvider(): void
    {
        $cnpjWs = 'https://publica.cnpj.ws/cnpj/03312791000183';
        $receitaWs = 'https://receitaws.com.br/v1/cnpj/03312791000183';
        $http = new MockHttpClient([
            $cnpjWs => ['status' => 503, 'body' => '{"error":"unavailable"}'],
            $receitaWs => ['body' => json_encode([
                'status' => 'OK',
                'cnpj' => '03.312.791/0001-83',
                'nome' => 'EMPRESA FALLBACK LTDA',
                'abertura' => '12/08/1999',
                'telefone' => '(11) 3333-4444 / (11) 99999-8888',
            ], JSON_THROW_ON_ERROR)],
        ]);
        $options = new CnpjClientOptions(enableBrasilApi: false, enableCnpja: false);
        $result = (new CnpjClient($http, $options))->get('03312791000183');

        self::assertTrue($result->success);
        self::assertSame('EMPRESA FALLBACK LTDA', $result->data?->razaoSocial);
        self::assertSame('ReceitaWS', $result->data?->provedor);
        self::assertSame([$cnpjWs, $receitaWs], $http->requestedUrls);
    }

    public function testRejectsInvalidCnpjBeforeHttpRequest(): void
    {
        $http = new MockHttpClient([]);
        $this->expectException(InvalidCnpjException::class);
        try {
            (new CnpjClient($http))->get('00.000.000/0000-00');
        } finally {
            self::assertSame([], $http->requestedUrls);
        }
    }

    public function testReturnsErrorForUnknownProvider(): void
    {
        $result = (new CnpjClient(new MockHttpClient([])))->getFromProvider('03312791000183', 'Inexistente');
        self::assertFalse($result->success);
        self::assertStringContainsString("Provedor 'Inexistente' não encontrado", $result->errorMessage ?? '');
    }
}
