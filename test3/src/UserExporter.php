<?php

declare(strict_types=1);

final class UserExporter
{
    private JobStore $jobStore;

    public function __construct(JobStore $jobStore)
    {
        $this->jobStore = $jobStore;
    }

    public function export(string $jobId): void
    {
        $fileName = 'users_' . $jobId . '.csv';
        $filePath = $this->jobStore->exportPath($fileName);
        $fp = fopen($filePath, 'wb');

        if ($fp === false) {
            throw new RuntimeException('Не удалось создать файл выгрузки.');
        }

        try {
            fwrite($fp, "\xEF\xBB\xBF");
            fputcsv($fp, ['Фамилия', 'Имя', 'Телефон', 'E-mail'], ';');

            $pdo = Database::getUnbufferedConnection();
            $stmt = $pdo->query(
                'SELECT last_name, first_name, phone, email FROM users ORDER BY id'
            );

            $rows = 0;

            while ($row = $stmt->fetch()) {
                fputcsv($fp, [$row['last_name'], $row['first_name'], $row['phone'], $row['email']], ';');
                $rows++;

                if ($rows % 10000 === 0) {
                    $this->jobStore->updateProgress($jobId, $rows);
                }
            }

            $stmt->closeCursor();
            $this->jobStore->markReady($jobId, $rows, $fileName);
        } catch (Throwable $e) {
            fclose($fp);

            if (is_file($filePath)) {
                unlink($filePath);
            }

            throw $e;
        }

        fclose($fp);
    }
}
