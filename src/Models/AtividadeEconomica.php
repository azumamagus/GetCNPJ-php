<?php

declare(strict_types=1);

namespace GetCNPJ\Models;

use JsonSerializable;

final class AtividadeEconomica implements JsonSerializable
{
    public function __construct(
        public ?string $codigo = null,
        public ?string $descricao = null,
    ) {
    }

    public function __toString(): string
    {
        return trim("{$this->codigo} - {$this->descricao}", ' -');
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
