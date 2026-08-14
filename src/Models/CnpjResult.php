<?php

declare(strict_types=1);

namespace GetCNPJ\Models;

use JsonSerializable;

final class CnpjResult implements JsonSerializable
{
    /** @param list<ProviderError> $errors */
    private function __construct(
        public readonly bool $success,
        public readonly ?CnpjData $data = null,
        public readonly ?string $errorMessage = null,
        public readonly array $errors = [],
    ) {
    }

    public static function success(CnpjData $data): self
    {
        return new self(true, $data);
    }

    /** @param list<ProviderError> $errors */
    public static function error(string $message, array $errors = []): self
    {
        return new self(false, null, $message, $errors);
    }

    /** @return list<string> */
    public function getFailedProviders(): array
    {
        return array_map(static fn (ProviderError $error): string => $error->providerName, $this->errors);
    }

    public function jsonSerialize(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'errorMessage' => $this->errorMessage,
            'errors' => $this->errors,
            'failedProviders' => $this->getFailedProviders(),
        ];
    }
}
