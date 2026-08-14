<?php

declare(strict_types=1);

namespace GetCNPJ\Services;

use GetCNPJ\Contracts\CnpjProviderInterface;
use GetCNPJ\Contracts\CnpjServiceInterface;
use GetCNPJ\Exceptions\InvalidCnpjException;
use GetCNPJ\Models\CnpjResult;
use GetCNPJ\Models\ProviderError;
use InvalidArgumentException;
use Throwable;

final class CnpjService implements CnpjServiceInterface
{
    /** @var list<CnpjProviderInterface> */
    private array $providers;

    /** @param iterable<CnpjProviderInterface> $providers */
    public function __construct(iterable $providers)
    {
        $this->providers = is_array($providers) ? array_values($providers) : iterator_to_array($providers, false);
        if ($this->providers === []) {
            throw new InvalidArgumentException('Pelo menos um provedor deve ser configurado.');
        }
        foreach ($this->providers as $provider) {
            if (!$provider instanceof CnpjProviderInterface) {
                throw new InvalidArgumentException('Todos os provedores devem implementar CnpjProviderInterface.');
            }
        }
        usort($this->providers, static fn (CnpjProviderInterface $a, CnpjProviderInterface $b): int => $a->getPriority() <=> $b->getPriority());
    }

    public function getCnpj(string $cnpj): CnpjResult
    {
        $errors = [];
        foreach ($this->providers as $provider) {
            try {
                $data = $provider->getCnpjData($cnpj);
                if ($data !== null) {
                    return CnpjResult::success($data);
                }
                $errors[] = new ProviderError($provider->getProviderName(), 'CNPJ não encontrado.');
            } catch (InvalidCnpjException $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                $errors[] = new ProviderError($provider->getProviderName(), $exception->getMessage(), $exception);
            }
        }

        return CnpjResult::error(
            sprintf('Não foi possível consultar o CNPJ. Tentativas: %d', count($errors)),
            $errors,
        );
    }

    public function getCnpjFromProvider(string $cnpj, string $providerName): CnpjResult
    {
        $provider = $this->findProvider($providerName);
        if ($provider === null) {
            $names = array_map(static fn (CnpjProviderInterface $item): string => $item->getProviderName(), $this->providers);
            return CnpjResult::error(sprintf(
                "Provedor '%s' não encontrado. Provedores disponíveis: %s",
                $providerName,
                implode(', ', $names),
            ));
        }

        try {
            $data = $provider->getCnpjData($cnpj);
            return $data !== null ? CnpjResult::success($data) : CnpjResult::error('CNPJ não encontrado.');
        } catch (InvalidCnpjException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $error = new ProviderError($provider->getProviderName(), $exception->getMessage(), $exception);
            return CnpjResult::error("Erro ao consultar CNPJ: {$exception->getMessage()}", [$error]);
        }
    }

    private function findProvider(string $providerName): ?CnpjProviderInterface
    {
        foreach ($this->providers as $provider) {
            if (strcasecmp($provider->getProviderName(), $providerName) === 0) {
                return $provider;
            }
        }
        return null;
    }
}
