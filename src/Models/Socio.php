<?php

declare(strict_types=1);

namespace GetCNPJ\Models;

use JsonSerializable;

final class Socio implements JsonSerializable
{
    public function __construct(
        public ?string $nome = null,
        public ?string $qualificacao = null,
    ) {
    }

    public function __toString(): string
    {
        return trim("{$this->nome} - {$this->qualificacao}", ' -');
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
