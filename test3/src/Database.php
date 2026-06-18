<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;
    /** @var array<string, string>|null */
    private static ?array $config = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = self::createConnection(true);
        }

        return self::$connection;
    }

    public static function getUnbufferedConnection(): PDO
    {
        return self::createConnection(false);
    }

    private static function createConnection(bool $buffered): PDO
    {
        $config = self::config();
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        if (!$buffered) {
            $options[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = false;
        }

        return new PDO(
            sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $config['DB_HOST'],
                $config['DB_NAME'],
                $config['DB_CHARSET']
            ),
            $config['DB_USER'],
            $config['DB_PASSWORD'],
            $options
        );
    }

    /**
     * @return array<string, string>
     */
    private static function config(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        $envPath = dirname(__DIR__) . '/.env';
        if (!is_file($envPath)) {
            throw new RuntimeException('Файл test3/.env не найден. Создайте его на основе test3/.env.example.');
        }

        $config = [];
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
                $key = trim($key);
                $value = trim($value);
                $value = trim($value, "\"'");

                if ($key !== '') {
                    $config[$key] = $value;
                }
            }
        }

        $requiredKeys = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_CHARSET'];
        foreach ($requiredKeys as $key) {
            if (!isset($config[$key]) || $config[$key] === '') {
                throw new RuntimeException(
                    sprintf('В test3/.env отсутствует обязательный параметр: %s', $key)
                );
            }
        }

        self::$config = $config;

        return self::$config;
    }
}
