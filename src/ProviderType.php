<?php

declare(strict_types=1);

namespace GetCNPJ;

enum ProviderType: string
{
    case CNPJWS = 'CNPJWS';
    case ReceitaWS = 'ReceitaWS';
    case BrasilAPI = 'BrasilAPI';
    case CNPJA = 'CNPJA';
}
