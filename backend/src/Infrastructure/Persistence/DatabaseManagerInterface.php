<?php

namespace App\Infrastructure\Persistence;

use PDO;
use PDOStatement;

interface DatabaseManagerInterface
{
    public function connect(): DatabaseManagerInterface;
    public function getPdo(): PDO;
    public function raw(string $sql): PDOStatement|false;
    public function run(string $sql, array $args = []): PDOStatement|false;
    public function rows(string $sql, array $args = [], int $fetchMode = PDO::FETCH_CLASS, ?string $class = null): array|false;
    public function row(string $sql, string $class, array $args = []): mixed;
    public function getById(string $table, int $id, int $fetchMode = PDO::FETCH_CLASS, ?string $class = null): mixed;
    public function count(string $sql, array $args = []): int;
    public function lastInsertId(): false|string;
    public function insert(string $table, array $data): false|string;
    public function update(string $table, array $data, array $where): int;
    public function delete(string $table, array $where, int $limit = 1): int;
    public function deleteAll(string $table): int;
    public function deleteById(string $table, int $id): int;
    public function deleteByIds(string $table, string $column, string $ids): int;
    public function batchInsert(string $table, array $fields, array $data): false|PDOStatement;


    public function startTransaction(): bool;
    public function commit(): bool;
    public function rollback(): bool;
}
