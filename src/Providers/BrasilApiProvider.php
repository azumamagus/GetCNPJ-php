<?php

declare(strict_types=1);

namespace GetCNPJ\Providers;

use GetCNPJ\Models\AtividadeEconomica;
use GetCNPJ\Models\CnpjData;
use GetCNPJ\Models\Endereco;
use GetCNPJ\Models\SimplesNacional;
use GetCNPJ\Models\Socio;

final class BrasilApiProvider extends CnpjProviderBase
{
    public function getProviderName(): string
    {
        return 'BrasilAPI';
    }

    public function getPriority(): int
    {
        return 3;
    }

    protected function getBaseUrl(): string
    {
        return 'https://brasilapi.com.br/api/cnpj/v1/';
    }

    protected function fetchData(string $cnpj): ?CnpjData
    {
        $response = $this->getJson($this->getBaseUrl() . $cnpj);
        $street = trim(implode(' ', array_filter([
            self::string($response['descricao_tipo_logradouro'] ?? null),
            self::string($response['logradouro'] ?? null),
        ])));
        $data = new CnpjData(
            cnpj: self::formatCnpj(self::string($response['cnpj'] ?? null)),
            razaoSocial: self::string($response['razao_social'] ?? null),
            nomeFantasia: self::string($response['nome_fantasia'] ?? null),
            dataAbertura: self::date($response['data_inicio_atividade'] ?? null),
            situacao: self::string($response['descricao_situacao_cadastral'] ?? null),
            dataSituacao: self::date($response['data_situacao_cadastral'] ?? null),
            tipo: self::string($response['descricao_matriz_filial'] ?? null),
            porte: self::string($response['descricao_porte'] ?? null),
            naturezaJuridica: self::string($response['natureza_juridica'] ?? null),
            capitalSocial: self::float($response['capital_social'] ?? null),
            endereco: new Endereco(
                cep: self::formatCep(self::string($response['cep'] ?? null)),
                logradouro: $street !== '' ? $street : null,
                numero: self::string($response['numero'] ?? null),
                complemento: self::string($response['complemento'] ?? null),
                bairro: self::string($response['bairro'] ?? null),
                municipio: self::string($response['municipio'] ?? null),
                uf: self::string($response['uf'] ?? null),
            ),
        );

        foreach (['ddd_telefone_1', 'ddd_telefone_2'] as $key) {
            $phone = self::formatPhone(self::string($response[$key] ?? null));
            if ($phone !== null) {
                $data->telefones[] = $phone;
            }
        }
        if (isset($response['cnae_fiscal'])) {
            $data->atividadePrincipal = new AtividadeEconomica(
                self::formatCnae($response['cnae_fiscal']),
                self::string($response['cnae_fiscal_descricao'] ?? null),
            );
        }
        foreach ($response['cnaes_secundarios'] ?? [] as $activity) {
            if (is_array($activity) && isset($activity['codigo'])) {
                $data->atividadesSecundarias[] = new AtividadeEconomica(
                    self::formatCnae($activity['codigo']),
                    self::string($activity['descricao'] ?? null),
                );
            }
        }
        foreach ($response['qsa'] ?? [] as $member) {
            if (is_array($member)) {
                $data->quadroSocietario[] = new Socio(
                    self::string($member['nome_socio'] ?? null),
                    self::string($member['qualificacao_socio'] ?? null),
                );
            }
        }
        $data->simples = new SimplesNacional(
            (bool) ($response['opcao_pelo_simples'] ?? false),
            self::date($response['data_opcao_pelo_simples'] ?? null),
            self::date($response['data_exclusao_do_simples'] ?? null),
            (bool) ($response['opcao_pelo_mei'] ?? false),
        );

        return $data;
    }
}
