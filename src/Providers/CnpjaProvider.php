<?php

declare(strict_types=1);

namespace GetCNPJ\Providers;

use GetCNPJ\Models\AtividadeEconomica;
use GetCNPJ\Models\CnpjData;
use GetCNPJ\Models\Endereco;
use GetCNPJ\Models\SimplesNacional;
use GetCNPJ\Models\Socio;

final class CnpjaProvider extends CnpjProviderBase
{
    public function getProviderName(): string
    {
        return 'CNPJA';
    }

    public function getPriority(): int
    {
        return 4;
    }

    protected function getBaseUrl(): string
    {
        return 'https://open.cnpja.com/office/';
    }

    protected function fetchData(string $cnpj): ?CnpjData
    {
        $response = $this->getJson($this->getBaseUrl() . $cnpj);
        $company = is_array($response['company'] ?? null) ? $response['company'] : [];
        $status = is_array($response['status'] ?? null) ? $response['status'] : [];
        $size = is_array($company['size'] ?? null) ? $company['size'] : [];
        $nature = is_array($company['nature'] ?? null) ? $company['nature'] : [];
        $address = is_array($response['address'] ?? null) ? $response['address'] : [];

        $data = new CnpjData(
            cnpj: self::formatCnpj(self::string($response['taxId'] ?? null)),
            razaoSocial: self::string($company['name'] ?? null),
            nomeFantasia: self::string($response['alias'] ?? null),
            dataAbertura: self::date($response['founded'] ?? null),
            situacao: self::string($status['text'] ?? null),
            dataSituacao: self::date($response['statusDate'] ?? null),
            tipo: (bool) ($response['head'] ?? false) ? 'MATRIZ' : 'FILIAL',
            porte: self::string($size['text'] ?? null),
            naturezaJuridica: $nature !== [] ? trim(sprintf('%s - %s', $nature['id'] ?? '', $nature['text'] ?? ''), ' -') : null,
            capitalSocial: self::float($company['equity'] ?? null),
            endereco: new Endereco(
                cep: self::formatCep(self::string($address['zip'] ?? null)),
                logradouro: self::string($address['street'] ?? null),
                numero: self::string($address['number'] ?? null),
                complemento: self::string($address['details'] ?? null),
                bairro: self::string($address['district'] ?? null),
                municipio: self::string($address['city'] ?? null),
                uf: self::string($address['state'] ?? null),
            ),
            ultimaAtualizacao: self::date($response['updated'] ?? null),
        );

        $firstEmail = $response['emails'][0] ?? null;
        if (is_array($firstEmail)) {
            $data->email = self::string($firstEmail['address'] ?? null);
        }
        foreach ($response['phones'] ?? [] as $phone) {
            if (is_array($phone)) {
                $formatted = self::formatPhone(self::string($phone['area'] ?? null), self::string($phone['number'] ?? null));
                if ($formatted !== null) {
                    $data->telefones[] = $formatted;
                }
            }
        }
        $main = $response['mainActivity'] ?? null;
        if (is_array($main)) {
            $data->atividadePrincipal = new AtividadeEconomica(
                self::formatCnae($main['id'] ?? null),
                self::string($main['text'] ?? null),
            );
        }
        foreach ($response['sideActivities'] ?? [] as $activity) {
            if (is_array($activity)) {
                $data->atividadesSecundarias[] = new AtividadeEconomica(
                    self::formatCnae($activity['id'] ?? null),
                    self::string($activity['text'] ?? null),
                );
            }
        }
        foreach ($company['members'] ?? [] as $member) {
            if (!is_array($member)) {
                continue;
            }
            $person = is_array($member['person'] ?? null) ? $member['person'] : [];
            $role = is_array($member['role'] ?? null) ? $member['role'] : [];
            $qualification = $role !== [] ? trim(sprintf('%s-%s', $role['id'] ?? '', $role['text'] ?? ''), '-') : null;
            $data->quadroSocietario[] = new Socio(self::string($person['name'] ?? null), $qualification);
        }
        $simple = is_array($company['simples'] ?? null) ? $company['simples'] : [];
        $simei = is_array($company['simei'] ?? null) ? $company['simei'] : [];
        $data->simples = new SimplesNacional(
            (bool) ($simple['optant'] ?? false),
            self::date($simple['since'] ?? null),
            null,
            (bool) ($simei['optant'] ?? false),
        );

        return $data;
    }
}
