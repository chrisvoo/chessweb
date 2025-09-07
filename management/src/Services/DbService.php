<?php

namespace Scacchilatorre\Management\Services;

use InvalidArgumentException;
use PDO;

class DbService
{
    public const HOST = 'host';
    public const DB_NAME = 'db_name';
    public const USER = 'user';
    public const PASSWORD = 'password';

    private PDO $connection;

    public function __construct(array $config)
    {
        $this->connection = $this->establishConnection($config);
    }

    private function validate(array $config): void
    {
        if (empty($config[self::HOST]) || !is_string($config[self::HOST])) {
            throw new InvalidArgumentException('Database host cannot be empty');
        }

        if (empty($config[self::DB_NAME]) || !is_string($config[self::DB_NAME])) {
            throw new InvalidArgumentException('Database name cannot be empty');
        }

        if (empty($config[self::USER]) || !is_string($config[self::USER])) {
            throw new InvalidArgumentException('Database user cannot be empty');
        }

        if (empty($config[self::PASSWORD]) || !is_string($config[self::PASSWORD])) {
            throw new InvalidArgumentException('Database password cannot be empty');
        }
    }

    private function establishConnection(array $config): PDO
    {
        $this->validate($config);

        $connection = new PDO(
            'mysql:host=' . $config[self::HOST] .
            ';dbname=' . $config[self::DB_NAME] .
            ';charset=utf8',
            $config[self::USER],
            $config[self::PASSWORD]
        );
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $connection;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function resetDb(): void
    {
        $stmt = $this->connection->prepare('DELETE FROM articles');
        $stmt->execute();
        $stmt = $this->connection->prepare('DELETE FROM categories');
        $stmt->execute();
        $stmt = $this->connection->prepare('DELETE FROM tags');
        $stmt->execute();
    }
}
