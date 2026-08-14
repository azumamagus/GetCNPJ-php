<?php

declare(strict_types=1);

namespace GetCNPJ\Providers;

use GetCNPJ\Models\AtividadeEconomica;
use GetCNPJ\Models\CnpjData;
use GetCNPJ\Models\Endereco;
use GetCNPJ\Models\InscricaoEstadual;
use GetCNPJ\Models\SimplesNacional;
use GetCNPJ\Models\Socio;

final class CnpjWsProvider extends CnpjProviderBase
{
    public function getProviderName(): string
    {
        return 'CNPJWS';
    }

    public function getPriority(): int
    {
        return 1;
    }

    protected function getBaseUrl(): string
    {
        return 'https://publica.cnpj.ws/cnpj/';
    }

    protected function fetchData(string $cnpj): ?CnpjData
    {
        $response = $this->getJson($this->getBaseUrl() . $cnpj);
        $establishment = $response['estabelecimento'] ?? null;
        if (!is_array($establishment)) {
            return null;
        }

        $city = is_array($establishment['cidade'] ?? null) ? $establishment['cidade'] : [];
        $state = is_array($establishment['estado'] ?? null) ? $establishment['estado'] : [];
        $size = is_array($response['porte'] ?? null) ? $response['porte'] : [];
        $nature = is_array($response['natureza_juridica'] ?? null) ? $response['natureza_juridica'] : [];
        $street = trim(implode(' ', array_filter([
            self::string($establishment['tipo_logradouro'] ?? null),
            self::string($establishment['logradouro'] ?? null),
        ])));

        $data = new CnpjData(
            cnpj: self::formatCnpj(self::string($establishment['cnpj'] ?? null)),
            razaoSocial: self::string($response['razao_social'] ?? null),
            nomeFantasia: self::string($establishment['nome_fantasia'] ?? null),
            dataAbertura: self::date($establishment['data_inicio_atividade'] ?? null),
            situacao: self::string($establishment['situacao_cadastral'] ?? null),
            dataSituacao: self::date($establishment['data_situacao_cadastral'] ?? null),
            tipo: self::string($establishment['tipo'] ?? null),
            porte: self::string($size['descricao'] ?? null),
            naturezaJuridica: $nature !== [] ? trim(sprintf('%s - %s', $nature['id'] ?? '', $nature['descricao'] ?? ''), ' -') : null,
            capitalSocial: self::float($response['capital_social'] ?? null),
            endereco: new Endereco(
                cep: self::formatCep(self::string($establishment['cep'] ?? null)),
                logradouro: $street !== '' ? $street : null,
                numero: self::string($establishment['numero'] ?? null),
                complemento: self::string($establishment['complemento'] ?? null),
                bairro: self::string($establishment['bairro'] ?? null),
                municipio: self::string($city['nome'] ?? null),
                uf: self::string($state['sigla'] ?? null),
            ),
            email: self::string($establishment['email'] ?? null),
            ultimaAtualizacao: self::date($response['atualizado_em'] ?? null),
        );

        foreach ([['ddd1', 'telefone1'], ['ddd2', 'telefone2']] as [$areaKey, $phoneKey]) {
            $phone = self::formatPhone(
                self::string($establishment[$areaKey] ?? null),
                self::string($establishment[$phoneKey] ?? null),
            );
            if ($phone !== null) {
                $data->telefones[] = $phone;
            }
        }

        $main = $establishment['atividade_principal'] ?? null;
        if (is_array($main)) {
            $data->atividadePrincipal = new AtividadeEconomica(
                self::formatCnae($main['subclasse'] ?? null),
                self::string($main['descricao'] ?? null),
            );
        }

        foreach ($establishment['atividades_secundarias'] ?? [] as $activity) {
            if (is_array($activity)) {
                $data->atividadesSecundarias[] = new AtividadeEconomica(
                    self::formatCnae($activity['subclasse'] ?? null),
                    self::string($activity['descricao'] ?? null),
                );
            }
        }

        foreach ($response['socios'] ?? [] as $member) {
            if (!is_array($member)) {
                continue;
            }
            $role = is_array($member['qualificacao_socio'] ?? null) ? $member['qualificacao_socio'] : [];
            $data->quadroSocietario[] = new Socio(
                self::string($member['nome'] ?? null),
                self::string($role['descricao'] ?? null),
            );
        }

        foreach ($establishment['inscricoes_estaduais'] ?? [] as $registration) {
            if (!is_array($registration)) {
                continue;
            }
            $registrationState = is_array($registration['estado'] ?? null) ? $registration['estado'] : [];
            $data->inscricoesEstaduais[] = new InscricaoEstadual(
                self::string($registration['inscricao_estadual'] ?? null),
                self::string($registrationState['sigla'] ?? null),
                (bool) ($registration['ativo'] ?? false),
                self::date($registration['atualizado_em'] ?? null),
            );
        }

        $simple = $response['simples'] ?? null;
        if (is_array($simple)) {
            $data->simples = new SimplesNacional(
                strcasecmp(self::string($simple['simples'] ?? null) ?? '', 'Sim') === 0,
                self::date($simple['data_opcao_simples'] ?? null),
                self::date($simple['data_exclusao_simples'] ?? null),
                strcasecmp(self::string($simple['mei'] ?? null) ?? '', 'Sim') === 0,
            );
        }

        return $data;
    }
}
