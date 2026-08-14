<?php

declare(strict_types=1);

namespace GetCNPJ\Models;

use DateTimeImmutable;
use JsonSerializable;

final class SimplesNacional implements JsonSerializable
{
    public function __construct(
        public bool $optante = false,
        public ?DateTimeImmutable $dataOpcao = null,
        public ?DateTimeImmutable $dataExclusao = null,
        public bool $optanteSimei = false,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'optante' => $this->optante,
            'dataOpcao' => $this->dataOpcao?->format('Y-m-d'),
            'dataExclusao' => $this->dataExclusao?->format('Y-m-d'),
            'optanteSimei' => $this->optanteSimei,
        ];
    }
}
