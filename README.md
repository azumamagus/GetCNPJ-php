<p align="center">
  <a href="https://packagist.org/packages/azumamagus/get-cnpj">
    <img src="https://raw.githubusercontent.com/azumamagus/GetCNPJ-php/main/logo.png" width="280" alt="GetCNPJ">
  </a>
</p>

<h1 align="center">GetCNPJ para PHP</h1>

<p align="center">
  Consulte dados públicos de empresas brasileiras com resposta padronizada,<br>
  validação de CNPJ e fallback automático entre múltiplos provedores.
</p>

<p align="center">
  <a href="https://packagist.org/packages/azumamagus/get-cnpj"><img src="https://img.shields.io/packagist/v/azumamagus/get-cnpj.svg?style=flat-square" alt="Versão no Packagist"></a>
  <a href="https://packagist.org/packages/azumamagus/get-cnpj"><img src="https://img.shields.io/packagist/dt/azumamagus/get-cnpj.svg?style=flat-square" alt="Downloads"></a>
  <a href="https://github.com/azumamagus/GetCNPJ-php/actions/workflows/tests.yml"><img src="https://github.com/azumamagus/GetCNPJ-php/actions/workflows/tests.yml/badge.svg" alt="Testes"></a>
  <a href="https://packagist.org/packages/azumamagus/get-cnpj"><img src="https://img.shields.io/packagist/php-v/azumamagus/get-cnpj.svg?style=flat-square" alt="Versões do PHP"></a>
  <a href="LICENSE"><img src="https://img.shields.io/packagist/l/azumamagus/get-cnpj.svg?style=flat-square" alt="Licença MIT"></a>
</p>

<p align="center">
  <strong>PHP puro</strong> · <strong>Laravel</strong> · <strong>CodeIgniter 4</strong>
</p>

---

## Instalação

```bash
composer require azumamagus/get-cnpj
```

## Comece em 30 segundos

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use GetCNPJ\CnpjClient;

$result = (new CnpjClient())->get('03.312.791/0001-83');

if ($result->success) {
    echo $result->data->razaoSocial . PHP_EOL;
    echo $result->data->endereco?->getEnderecoCompleto() . PHP_EOL;
    echo 'Provedor: ' . $result->data->provedor . PHP_EOL;
} else {
    echo $result->errorMessage . PHP_EOL;
}
```

O CNPJ pode ser informado com ou sem máscara. A biblioteca valida os dígitos verificadores antes de realizar qualquer chamada HTTP.

## Por que usar?

- **Fallback automático:** se um serviço estiver indisponível, o próximo provedor é consultado.
- **Resposta padronizada:** trabalhe com o mesmo modelo de dados, independentemente da API utilizada.
- **Quatro provedores:** CNPJ.WS, ReceitaWS, BrasilAPI e CNPJA.
- **Inscrição estadual:** retornada pelo CNPJ.WS quando disponível.
- **Rate limiting:** limite independente por provedor, com Sliding Window.
- **Validação local:** CNPJs inválidos não consomem requisições externas.
- **Integrações nativas:** PHP puro, container e Facade no Laravel e Service Discovery no CodeIgniter 4.
- **Extensível e testável:** cliente HTTP PSR-18 injetável e objetos tipados serializáveis em JSON.

## Compatibilidade

| Ambiente | Versão | Forma de uso |
|---|---:|---|
| PHP puro | PHP 8.1+ | `new CnpjClient()` |
| Laravel | 10, 11, 12 e 13 | Injeção, singleton e Facade |
| CodeIgniter | 4.x | `service('getCnpj')` |
| HTTP | PSR-18 | Guzzle incluído ou cliente customizado |

> O núcleo é testado em PHP 8.1. As integrações com as versões atuais de Laravel e CodeIgniter 4 são testadas em PHP 8.2, 8.3 e 8.4.

## Provedores e fallback

Os provedores são consultados nesta ordem:

| Prioridade | Provedor | Inscrição estadual | Endereço | QSA | Simples Nacional |
|---:|---|:---:|:---:|:---:|:---:|
| 1 | [CNPJ.WS](https://publica.cnpj.ws/) | ✅ | ✅ | ✅ | ✅ |
| 2 | [ReceitaWS](https://receitaws.com.br/) | — | ✅ | ✅ | ✅ |
| 3 | [BrasilAPI](https://brasilapi.com.br/) | — | ✅ | ✅ | ✅ |
| 4 | [CNPJA](https://cnpja.com/) | — | ✅ | ✅ | ✅ |

Quando um provedor falha, a exceção é registrada em `$result->errors` e a consulta segue para o próximo. Se todos falharem, o resultado contém os detalhes de cada tentativa.

## Sumário

- [PHP puro](#php-puro)
- [Laravel](#laravel)
- [CodeIgniter 4](#codeigniter-4)
- [Provedor específico](#provedor-específico)
- [Configuração avançada](#configuração-avançada)
- [Modelo retornado](#modelo-retornado)
- [Tratamento de erros](#tratamento-de-erros)
- [Rate limiting](#rate-limiting)
- [Desenvolvimento e testes](#desenvolvimento-e-testes)
- [Publicação de versões](#publicação-de-versões)

## PHP puro

```php
use GetCNPJ\CnpjClient;

$client = new CnpjClient();
$result = $client->get('03312791000183');

if (!$result->success) {
    foreach ($result->errors as $error) {
        echo "{$error->providerName}: {$error->errorMessage}" . PHP_EOL;
    }

    return;
}

$empresa = $result->data;

echo $empresa->cnpj . PHP_EOL;
echo $empresa->razaoSocial . PHP_EOL;
echo $empresa->nomeFantasia . PHP_EOL;
echo $empresa->situacao . PHP_EOL;

foreach ($empresa->telefones as $telefone) {
    echo $telefone . PHP_EOL;
}

foreach ($empresa->inscricoesEstaduais as $inscricao) {
    echo $inscricao . PHP_EOL;
}
```

## Laravel

O Laravel encontra automaticamente o Service Provider e a Facade declarados no pacote. Não é necessário editar `bootstrap/providers.php` ou `config/app.php`.

### Injeção de dependência

```php
<?php

namespace App\Http\Controllers;

use GetCNPJ\CnpjClient;
use Illuminate\Http\JsonResponse;

final class EmpresaController extends Controller
{
    public function show(string $cnpj, CnpjClient $client): JsonResponse
    {
        return response()->json($client->get($cnpj));
    }
}
```

O cliente é registrado como singleton e também pode ser resolvido diretamente:

```php
$client = app(\GetCNPJ\CnpjClient::class);
```

### Facade

```php
use GetCNPJ\Integrations\Laravel\Facades\GetCNPJ;
use GetCNPJ\ProviderType;

$result = GetCNPJ::get('03.312.791/0001-83');

$brasilApi = GetCNPJ::getFromProvider(
    '03312791000183',
    ProviderType::BrasilAPI,
);
```

### Configuração do Laravel

Publique o arquivo `config/getcnpj.php`:

```bash
php artisan vendor:publish --tag=getcnpj-config
```

Configure pelo `.env`:

```dotenv
GETCNPJ_TIMEOUT=30
GETCNPJ_MAX_REQUESTS_PER_MINUTE=3
GETCNPJ_ENABLE_CNPJ_WS=true
GETCNPJ_ENABLE_RECEITA_WS=true
GETCNPJ_ENABLE_BRASIL_API=true
GETCNPJ_ENABLE_CNPJA=true
```

Se houver uma implementação de `Psr\Http\Client\ClientInterface` registrada no container, ela será utilizada automaticamente. Caso contrário, o pacote usa o Guzzle incluído.

## CodeIgniter 4

Com o Service Discovery padrão habilitado, o CodeIgniter encontra automaticamente `GetCNPJ\Config\Services`:

```php
<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

final class Empresa extends BaseController
{
    public function show(string $cnpj): ResponseInterface
    {
        $result = service('getCnpj')->get($cnpj);

        return $this->response->setJSON($result);
    }
}
```

`service('getCnpj')` retorna uma instância compartilhada. Para criar uma nova instância:

```php
$client = single_service('getCnpj');
```

Também é possível acessar o serviço diretamente:

```php
$client = \GetCNPJ\Config\Services::getCnpj();
```

Configure pelo `.env` do CodeIgniter:

```dotenv
getcnpj.timeout = 30
getcnpj.maxRequestsPerMinute = 3
getcnpj.enableCnpjWs = true
getcnpj.enableReceitaWs = true
getcnpj.enableBrasilApi = true
getcnpj.enableCnpja = true
```

Se `discoverInComposer` estiver desabilitado em `app/Config/Modules.php`, use `\GetCNPJ\Config\Services::getCnpj()` ou permita `azumamagus/get-cnpj` na descoberta de pacotes.

## Provedor específico

Use o enum para evitar erros de digitação:

```php
use GetCNPJ\CnpjClient;
use GetCNPJ\ProviderType;

$client = new CnpjClient();

$result = $client->getFromProvider(
    '03312791000183',
    ProviderType::BrasilAPI,
);
```

Também são aceitas as strings `CNPJWS`, `ReceitaWS`, `BrasilAPI` e `CNPJA`, sem diferenciação entre maiúsculas e minúsculas.

## Configuração avançada

```php
use GetCNPJ\CnpjClient;
use GetCNPJ\CnpjClientOptions;

$options = new CnpjClientOptions(
    timeout: 60,
    maxRequestsPerMinute: 5,
    enableCnpjWs: true,
    enableReceitaWs: true,
    enableBrasilApi: true,
    enableCnpja: false,
);

$client = new CnpjClient(options: $options);
```

### Cliente HTTP customizado

Qualquer cliente PSR-18 pode ser injetado:

```php
use GetCNPJ\CnpjClient;
use GuzzleHttp\Client;

$http = new Client([
    'timeout' => 15,
    'proxy' => 'http://proxy.exemplo:8080',
    'http_errors' => false,
]);

$client = new CnpjClient(httpClient: $http);
```

Isso também facilita testes, observabilidade, proxies e políticas HTTP próprias da aplicação.

## Modelo retornado

Uma consulta sempre retorna `GetCNPJ\Models\CnpjResult`:

```php
$result->success;         // bool
$result->data;            // ?CnpjData
$result->errorMessage;    // ?string
$result->errors;          // ProviderError[]
$result->getFailedProviders();
```

Em caso de sucesso, `CnpjData` oferece:

| Grupo | Propriedades |
|---|---|
| Identificação | `cnpj`, `razaoSocial`, `nomeFantasia` |
| Cadastro | `dataAbertura`, `situacao`, `dataSituacao`, `tipo`, `porte` |
| Jurídico | `naturezaJuridica`, `capitalSocial` |
| Localização | `endereco` |
| Atividades | `atividadePrincipal`, `atividadesSecundarias` |
| Pessoas | `quadroSocietario` |
| Contato | `telefones`, `email` |
| Fiscal | `inscricoesEstaduais`, `simples` |
| Origem | `ultimaAtualizacao`, `provedor` |

Datas são instâncias de `DateTimeImmutable`. Todos os modelos implementam `JsonSerializable`:

```php
echo json_encode(
    $result,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
);
```

Exemplo resumido:

```json
{
  "success": true,
  "data": {
    "cnpj": "03.312.791/0001-83",
    "razaoSocial": "SOS SYSTEM TECNOLOGIA EM INFORMATICA LTDA",
    "situacao": "Ativa",
    "telefones": ["(14) 3206-2096"],
    "provedor": "CNPJWS"
  },
  "errorMessage": null,
  "errors": []
}
```

## Tratamento de erros

### CNPJ inválido

Um CNPJ com tamanho ou dígitos verificadores inválidos lança `InvalidCnpjException` antes da consulta:

```php
use GetCNPJ\Exceptions\InvalidCnpjException;

try {
    $result = (new CnpjClient())->get('00.000.000/0000-00');
} catch (InvalidCnpjException $exception) {
    echo $exception->getMessage();
}
```

### Falha dos provedores

Erros de rede ou respostas inválidas não interrompem o fallback. Se todos os provedores falharem:

```php
if (!$result->success) {
    echo $result->errorMessage . PHP_EOL;

    foreach ($result->errors as $error) {
        echo "{$error->providerName}: {$error->errorMessage}" . PHP_EOL;
    }
}
```

## Rate limiting

O Sliding Window mantém um contador separado para cada provedor. Por padrão são permitidas três requisições por minuto para cada serviço. Quando o limite é atingido, a chamada aguarda até a requisição mais antiga sair da janela.

O controle permanece em memória durante a vida da instância de `CnpjClient`. Em aplicações distribuídas, configure limites também na infraestrutura ou implemente `RateLimiterInterface` com armazenamento compartilhado.

## Uso responsável

Este pacote consulta APIs públicas de terceiros. Portanto:

- respeite os termos e limites de cada provedor;
- espere indisponibilidades e mudanças externas de contrato;
- não trate os dados como substitutos de uma certidão oficial;
- use cache quando realizar consultas repetidas;
- observe a legislação aplicável ao tratamento e armazenamento de dados.

## Desenvolvimento e testes

```bash
git clone https://github.com/azumamagus/GetCNPJ-php.git
cd GetCNPJ-php
composer install
composer test
```

A suíte usa respostas HTTP simuladas para validar CNPJ, mapeamento, fallback e rate limiting. Também inicializa ambientes reais dos frameworks para testar container e Facade do Laravel e serviços do CodeIgniter 4.

O GitHub Actions executa:

- instalação de produção e validação de sintaxe no PHP 8.1;
- suíte completa com integrações nos PHP 8.2, 8.3 e 8.4.

## Publicação de versões

O pacote está disponível em [packagist.org/packages/azumamagus/get-cnpj](https://packagist.org/packages/azumamagus/get-cnpj).

Novas versões são publicadas automaticamente ao enviar uma tag semântica:

```bash
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0
```

O workflow valida a tag, o Composer e os testes antes de notificar a API oficial do Packagist. Pré-lançamentos como `v1.1.0-beta.1` também são aceitos.

> Uma versão estável publicada no Packagist é imutável. Para corrigir uma versão, crie uma nova tag; nunca mova ou recrie uma tag existente.

## Contribuindo

Issues e pull requests são bem-vindos. Ao contribuir:

1. crie uma branch para a alteração;
2. adicione ou atualize os testes;
3. execute `composer test`;
4. abra um pull request descrevendo o comportamento alterado.

## Licença

Distribuído sob a licença MIT. Consulte [LICENSE](LICENSE).

---

<p align="center">
  Feito para tornar consultas de CNPJ simples, resilientes e independentes de framework.
</p>
