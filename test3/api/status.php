<?php

declare(strict_types=1);

$baseDir = require __DIR__ . '/bootstrap.php';

$jobId = isset($_GET['job_id']) ? (string) $_GET['job_id'] : '';

if ($jobId === '' || !preg_match('/^[a-f0-9]{32}$/', $jobId)) {
    test3_json_response(['error' => 'Некорректный идентификатор задачи.'], 400);
    exit;
}

$jobStore = new JobStore($baseDir . '/exports');
$job = $jobStore->get($jobId);

if ($job === null) {
    test3_json_response(['error' => 'Задача не найдена.'], 404);
    exit;
}

$progress = $job['total'] > 0
    ? (int) round(($job['rows'] / $job['total']) * 100)
    : 0;

$payload = [
    'job_id' => $job['job_id'],
    'status' => $job['status'],
    'rows' => $job['rows'],
    'total' => $job['total'],
    'progress' => min(100, $progress),
];

if ($job['status'] === 'ready') {
    $payload['download_url'] = sprintf(
        'api/download.php?job_id=%s&token=%s',
        rawurlencode($job['job_id']),
        rawurlencode($job['token'])
    );
}

if ($job['status'] === 'error') {
    $payload['error'] = $job['error'];
}

test3_json_response($payload);
