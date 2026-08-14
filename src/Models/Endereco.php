<?php

declare(strict_types=1);

namespace GetCNPJ\Models;

use JsonSerializable;

final class Endereco implements JsonSerializable
{
    public function __construct(
        public ?string $cep = null,
        public ?string $logradouro = null,
        public ?string $numero = null,
        public ?string $complemento = null,
        public ?string $bairro = null,
        public ?string $municipio = null,
        public ?string $uf = null,
    ) {
    }

    public function getEnderecoCompleto(): string
    {
        $endereco = trim(sprintf('%s, %s', $this->logradouro ?? '', $this->numero ?? ''), ' ,');
        if ($this->complemento !== null && trim($this->complemento) !== '') {
            $endereco .= " - {$this->complemento}";
        }

        $local = trim(sprintf('%s, %s/%s', $this->bairro ?? '', $this->municipio ?? '', $this->uf ?? ''), ' ,/');
        if ($local !== '') {
            $endereco .= " - {$local}";
        }
        if ($this->cep !== null && trim($this->cep) !== '') {
            $endereco .= " - CEP: {$this->cep}";
        }

        return ltrim($endereco, ' -');
    }

    public function __toString(): string
    {
        return $this->getEnderecoCompleto();
    }

    public function jsonSerialize(): array
    {
        return [...get_object_vars($this), 'enderecoCompleto' => $this->getEnderecoCompleto()];
    }
}
