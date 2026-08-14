<?php

declare(strict_types=1);

namespace GetCNPJ\Providers;

use GetCNPJ\Models\AtividadeEconomica;
use GetCNPJ\Models\CnpjData;
use GetCNPJ\Models\Endereco;
use GetCNPJ\Models\SimplesNacional;
use GetCNPJ\Models\Socio;

final class ReceitaWsProvider extends CnpjProviderBase
{
    public function getProviderName(): string
    {
        return 'ReceitaWS';
    }

    public function getPriority(): int
    {
        return 2;
    }

    protected function getBaseUrl(): string
    {
        return 'https://receitaws.com.br/v1/cnpj/';
    }

    protected function fetchData(string $cnpj): ?CnpjData
    {
        $response = $this->getJson($this->getBaseUrl() . $cnpj);
        if (strcasecmp(self::string($response['status'] ?? null) ?? '', 'ERROR') === 0) {
            return null;
        }

        $data = new CnpjData(
            cnpj: self::string($response['cnpj'] ?? null),
            razaoSocial: self::string($response['nome'] ?? null),
            nomeFantasia: self::string($response['fantasia'] ?? null),
            dataAbertura: self::date($response['abertura'] ?? null),
            situacao: self::string($response['situacao'] ?? null),
            dataSituacao: self::date($response['data_situacao'] ?? null),
            tipo: self::string($response['tipo'] ?? null),
            porte: self::string($response['porte'] ?? null),
            naturezaJuridica: self::string($response['natureza_juridica'] ?? null),
            capitalSocial: self::float($response['capital_social'] ?? null),
            endereco: new Endereco(
                cep: self::string($response['cep'] ?? null),
                logradouro: self::string($response['logradouro'] ?? null),
                numero: self::string($response['numero'] ?? null),
                complemento: self::string($response['complemento'] ?? null),
                bairro: self::string($response['bairro'] ?? null),
                municipio: self::string($response['municipio'] ?? null),
                uf: self::string($response['uf'] ?? null),
            ),
            email: self::string($response['email'] ?? null),
            ultimaAtualizacao: self::date($response['ultima_atualizacao'] ?? null),
        );

        $phones = preg_split('/[\/,]/', self::string($response['telefone'] ?? null) ?? '', -1, PREG_SPLIT_NO_EMPTY);
        $data->telefones = array_values(array_map('trim', $phones ?: []));

        $main = $response['atividade_principal'][0] ?? null;
        if (is_array($main)) {
            $data->atividadePrincipal = new AtividadeEconomica(
                self::string($main['code'] ?? null),
                self::string($main['text'] ?? null),
            );
        }
        foreach ($response['atividades_secundarias'] ?? [] as $activity) {
            if (is_array($activity)) {
                $data->atividadesSecundarias[] = new AtividadeEconomica(
                    self::string($activity['code'] ?? null),
                    self::string($activity['text'] ?? null),
                );
            }
        }
        foreach ($response['qsa'] ?? [] as $member) {
            if (is_array($member)) {
                $data->quadroSocietario[] = new Socio(
                    self::string($member['nome'] ?? null),
                    self::string($member['qual'] ?? null),
                );
            }
        }

        $simple = $response['simples'] ?? null;
        if (is_array($simple)) {
            $simei = is_array($response['simei'] ?? null) ? $response['simei'] : [];
            $data->simples = new SimplesNacional(
                (bool) ($simple['optante'] ?? false),
                self::date($simple['data_opcao'] ?? null),
                self::date($simple['data_exclusao'] ?? null),
                (bool) ($simei['optante'] ?? false),
            );
        }

        return $data;
    }
}
