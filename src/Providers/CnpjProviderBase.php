<?php

declare(strict_types=1);

namespace GetCNPJ\Providers;

use DateTimeImmutable;
use GetCNPJ\Contracts\CnpjProviderInterface;
use GetCNPJ\Contracts\RateLimiterInterface;
use GetCNPJ\Exceptions\InvalidCnpjException;
use GetCNPJ\Exceptions\ProviderException;
use GetCNPJ\Models\CnpjData;
use GuzzleHttp\Psr7\Request;
use JsonException;
use Psr\Http\Client\ClientInterface;
use Throwable;

abstract class CnpjProviderBase implements CnpjProviderInterface
{
    public function __construct(
        protected readonly ClientInterface $httpClient,
        protected readonly RateLimiterInterface $rateLimiter,
    ) {
    }

    abstract protected function getBaseUrl(): string;

    abstract protected function fetchData(string $cnpj): ?CnpjData;

    final public function getCnpjData(string $cnpj): ?CnpjData
    {
        try {
            $cnpj = self::validateAndNormalizeCnpj($cnpj);
            $this->rateLimiter->waitIfNeeded($this->getProviderName());
            $this->rateLimiter->recordRequest($this->getProviderName());
            $data = $this->fetchData($cnpj);
            if ($data !== null) {
                $data->provedor = $this->getProviderName();
            }

            return $data;
        } catch (InvalidCnpjException|ProviderException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new ProviderException(
                $this->getProviderName(),
                "Erro ao consultar CNPJ: {$exception->getMessage()}",
                $exception,
            );
        }
    }

    public function isAvailable(): bool
    {
        try {
            $response = $this->httpClient->sendRequest(new Request('GET', $this->getBaseUrl()));
            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 400;
        } catch (Throwable) {
            return false;
        }
    }

    /** @return array<string, mixed> */
    protected function getJson(string $url): array
    {
        $response = $this->httpClient->sendRequest(new Request('GET', $url, [
            'Accept' => 'application/json',
            'User-Agent' => 'GetCNPJ-PHP/1.0',
        ]));
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if ($status < 200 || $status >= 300) {
            throw new ProviderException(
                $this->getProviderName(),
                sprintf('Erro na requisição: HTTP %d%s', $status, $body !== '' ? " - {$body}" : ''),
            );
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ProviderException($this->getProviderName(), 'Resposta JSON inválida.', $exception);
        }

        if (!is_array($decoded)) {
            throw new ProviderException($this->getProviderName(), 'Resposta JSON inesperada.');
        }

        return $decoded;
    }

    public static function validateAndNormalizeCnpj(string $cnpj): string
    {
        $normalized = preg_replace('/\D+/', '', $cnpj) ?? '';
        if (strlen($normalized) !== 14 || preg_match('/^(\d)\1{13}$/', $normalized) === 1) {
            throw new InvalidCnpjException($cnpj);
        }

        $calculate = static function (string $digits, array $multipliers): int {
            $sum = 0;
            foreach ($multipliers as $index => $multiplier) {
                $sum += (int) $digits[$index] * $multiplier;
            }
            $remainder = $sum % 11;
            return $remainder < 2 ? 0 : 11 - $remainder;
        };

        $first = $calculate($normalized, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $second = $calculate($normalized, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        if ((int) $normalized[12] !== $first || (int) $normalized[13] !== $second) {
            throw new InvalidCnpjException($cnpj);
        }

        return $normalized;
    }

    protected static function formatCnpj(?string $cnpj): ?string
    {
        if ($cnpj === null || strlen($cnpj) !== 14) {
            return $cnpj;
        }
        return substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3)
            . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
    }

    protected static function formatCep(?string $cep): ?string
    {
        if ($cep === null || strlen($cep) !== 8) {
            return $cep;
        }
        return substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
    }

    protected static function formatCnae(string|int|null $cnae): ?string
    {
        if ($cnae === null || $cnae === '') {
            return $cnae === '' ? '' : null;
        }
        $digits = preg_replace('/\D+/', '', str_pad((string) $cnae, 7, '0', STR_PAD_LEFT)) ?? '';
        if (strlen($digits) !== 7) {
            return $digits;
        }
        return substr($digits, 0, 2) . '.' . substr($digits, 2, 2) . '-' . $digits[4] . '-' . substr($digits, 5, 2);
    }

    protected static function formatPhone(?string $area, ?string $number = null): ?string
    {
        $digits = preg_replace('/\D+/', '', ($area ?? '') . ($number ?? '')) ?? '';
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6));
        }
        if (strlen($digits) === 11) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7));
        }
        return $number === null ? $digits : sprintf('(%s) %s', $area, $number);
    }

    protected static function date(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        foreach (['!Y-m-d', '!d/m/Y', DATE_ATOM, 'Y-m-d\TH:i:s.uP', 'Y-m-d\TH:i:sP'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value);
            if ($date !== false) {
                return $date;
            }
        }
        try {
            return new DateTimeImmutable($value);
        } catch (Throwable) {
            return null;
        }
    }

    /** @param array<string, mixed> $data */
    protected static function value(array $data, string $key): mixed
    {
        return $data[$key] ?? null;
    }

    protected static function string(mixed $value): ?string
    {
        return is_string($value) || is_numeric($value) ? (string) $value : null;
    }

    protected static function float(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $normalized = str_replace(['.', ','], ['', '.'], (string) $value);
        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
