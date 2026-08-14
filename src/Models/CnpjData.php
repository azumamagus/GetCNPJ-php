<?php

declare(strict_types=1);

namespace GetCNPJ\Models;

use DateTimeImmutable;
use JsonSerializable;

final class CnpjData implements JsonSerializable
{
    /** @param list<AtividadeEconomica> $atividadesSecundarias
     *  @param list<Socio> $quadroSocietario
     *  @param list<string> $telefones
     *  @param list<InscricaoEstadual> $inscricoesEstaduais
     */
    public function __construct(
        public ?string $cnpj = null,
        public ?string $razaoSocial = null,
        public ?string $nomeFantasia = null,
        public ?DateTimeImmutable $dataAbertura = null,
        public ?string $situacao = null,
        public ?DateTimeImmutable $dataSituacao = null,
        public ?string $tipo = null,
        public ?string $porte = null,
        public ?string $naturezaJuridica = null,
        public ?float $capitalSocial = null,
        public ?Endereco $endereco = null,
        public ?AtividadeEconomica $atividadePrincipal = null,
        public array $atividadesSecundarias = [],
        public array $quadroSocietario = [],
        public array $telefones = [],
        public ?string $email = null,
        public array $inscricoesEstaduais = [],
        public ?SimplesNacional $simples = null,
        public ?DateTimeImmutable $ultimaAtualizacao = null,
        public ?string $provedor = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'cnpj' => $this->cnpj,
            'razaoSocial' => $this->razaoSocial,
            'nomeFantasia' => $this->nomeFantasia,
            'dataAbertura' => $this->dataAbertura?->format('Y-m-d'),
            'situacao' => $this->situacao,
            'dataSituacao' => $this->dataSituacao?->format('Y-m-d'),
            'tipo' => $this->tipo,
            'porte' => $this->porte,
            'naturezaJuridica' => $this->naturezaJuridica,
            'capitalSocial' => $this->capitalSocial,
            'endereco' => $this->endereco,
            'atividadePrincipal' => $this->atividadePrincipal,
            'atividadesSecundarias' => $this->atividadesSecundarias,
            'quadroSocietario' => $this->quadroSocietario,
            'telefones' => $this->telefones,
            'email' => $this->email,
            'inscricoesEstaduais' => $this->inscricoesEstaduais,
            'simples' => $this->simples,
            'ultimaAtualizacao' => $this->ultimaAtualizacao?->format(DATE_ATOM),
            'provedor' => $this->provedor,
        ];
    }
}
