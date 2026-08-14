<?php

declare(strict_types=1);

namespace GetCNPJ\Models;

use DateTimeImmutable;
use JsonSerializable;

final class InscricaoEstadual implements JsonSerializable
{
    public function __construct(
        public ?string $inscricao = null,
        public ?string $estado = null,
        public bool $ativo = false,
        public ?DateTimeImmutable $dataAtualizacao = null,
    ) {
    }

    public function __toString(): string
    {
        return sprintf('%s (%s) - %s', $this->inscricao, $this->estado, $this->ativo ? 'Ativa' : 'Inativa');
    }

    public function jsonSerialize(): array
    {
        return [
            'inscricao' => $this->inscricao,
            'estado' => $this->estado,
            'ativo' => $this->ativo,
            'dataAtualizacao' => $this->dataAtualizacao?->format(DATE_ATOM),
        ];
    }
}
