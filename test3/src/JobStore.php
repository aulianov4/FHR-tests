<?php

declare(strict_types=1);

final class JobStore
{
    private string $jobsDir;
    private string $exportsDir;

    public function __construct(string $baseDir)
    {
        $this->jobsDir = $baseDir . '/jobs';
        $this->exportsDir = $baseDir;

        if (!is_dir($this->jobsDir)) {
            mkdir($this->jobsDir, 0755, true);
        }
    }

    public function create(): array
    {
        $jobId = bin2hex(random_bytes(16));
        $token = bin2hex(random_bytes(16));

        $job = [
            'job_id' => $jobId,
            'token' => $token,
            'status' => 'processing',
            'rows' => 0,
            'total' => $this->countUsers(),
            'file' => null,
            'error' => null,
            'created_at' => time(),
        ];

        $this->write($jobId, $job);

        return $job;
    }

    public function get(string $jobId): ?array
    {
        $path = $this->jobPath($jobId);

        if (!is_file($path)) {
            return null;
        }

        $job = json_decode((string) file_get_contents($path), true);

        return is_array($job) ? $job : null;
    }

    public function updateProgress(string $jobId, int $rows): void
    {
        $job = $this->get($jobId);

        if ($job === null) {
            return;
        }

        $job['rows'] = $rows;
        $this->write($jobId, $job);
    }

    public function markReady(string $jobId, int $rows, string $fileName): void
    {
        $job = $this->get($jobId);

        if ($job === null) {
            return;
        }

        $job['status'] = 'ready';
        $job['rows'] = $rows;
        $job['file'] = $fileName;
        $this->write($jobId, $job);
    }

    public function markFailed(string $jobId, string $message): void
    {
        $job = $this->get($jobId);

        if ($job === null) {
            return;
        }

        $job['status'] = 'error';
        $job['error'] = $message;
        $this->write($jobId, $job);
    }

    public function exportPath(string $fileName): string
    {
        return $this->exportsDir . '/' . $fileName;
    }

    private function countUsers(): int
    {
        $pdo = Database::getConnection();
        $total = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

        return (int) $total;
    }

    private function write(string $jobId, array $job): void
    {
        file_put_contents(
            $this->jobPath($jobId),
            json_encode($job, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            LOCK_EX
        );
    }

    private function jobPath(string $jobId): string
    {
        return $this->jobsDir . '/' . $jobId . '.json';
    }
}
