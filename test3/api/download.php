<?php

declare(strict_types=1);

$baseDir = require __DIR__ . '/bootstrap.php';

$jobId = isset($_GET['job_id']) ? (string) $_GET['job_id'] : '';
$token = isset($_GET['token']) ? (string) $_GET['token'] : '';

if ($jobId === '' || $token === '' || !preg_match('/^[a-f0-9]{32}$/', $jobId) || !preg_match('/^[a-f0-9]{32}$/', $token)) {
    http_response_code(400);
    echo 'Некорректные параметры.';
    exit;
}

$jobStore = new JobStore($baseDir . '/exports');
$job = $jobStore->get($jobId);

if ($job === null || !hash_equals($job['token'], $token) || $job['status'] !== 'ready' || empty($job['file'])) {
    http_response_code(404);
    echo 'Файл не найден.';
    exit;
}

$filePath = $jobStore->exportPath($job['file']);

if (!is_file($filePath)) {
    http_response_code(404);
    echo 'Файл не найден.';
    exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="users.csv"');
header('Content-Length: ' . (string) filesize($filePath));
header('Cache-Control: no-store');

readfile($filePath);
