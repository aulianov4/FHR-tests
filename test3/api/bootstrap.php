<?php

declare(strict_types=1);

$baseDir = dirname(__DIR__);

require_once $baseDir . '/src/Database.php';
require_once $baseDir . '/src/JobStore.php';
require_once $baseDir . '/src/UserExporter.php';

function test3_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

return $baseDir;
