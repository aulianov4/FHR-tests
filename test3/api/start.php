<?php

declare(strict_types=1);

$baseDir = require __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    test3_json_response(['error' => 'Метод не поддерживается.'], 405);
    exit;
}

set_time_limit(0);
ignore_user_abort(true);

$jobStore = new JobStore($baseDir . '/exports');
$job = $jobStore->create();

test3_json_response([
    'job_id' => $job['job_id'],
    'token' => $job['token'],
    'status' => $job['status'],
    'total' => $job['total'],
]);

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', '1');
    }

    @ini_set('zlib.output_compression', '0');
    @ini_set('implicit_flush', '1');

    for ($i = 0; $i < ob_get_level(); $i++) {
        ob_end_flush();
    }

    flush();
}

try {
    (new UserExporter($jobStore))->export($job['job_id']);
} catch (Throwable $e) {
    $jobStore->markFailed($job['job_id'], $e->getMessage());
}
