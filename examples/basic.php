<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use GetCNPJ\CnpjClient;

$cnpj = $argv[1] ?? '03.312.791/0001-83';
$result = (new CnpjClient())->get($cnpj);

if (!$result->success) {
    fwrite(STDERR, ($result->errorMessage ?? 'Erro desconhecido') . PHP_EOL);
    foreach ($result->errors as $error) {
        fwrite(STDERR, "- {$error->providerName}: {$error->errorMessage}" . PHP_EOL);
    }
    exit(1);
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
