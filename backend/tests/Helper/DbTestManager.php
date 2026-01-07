<?php

namespace Tests\Helper;

use App\Infrastructure\Persistence\DatabaseManagerInterface;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;

class DbTestManager implements DatabaseManagerInterface
{
    private ?PDO $pdo = null;

    public function __construct(
        private readonly string $dns,
        private readonly string $username,
        private readonly string $password,
        private readonly LoggerInterface $logger,
        private readonly array $options = [],
    ) {
    }

    public function connect(): DatabaseManagerInterface
    {
        // If a connection already exists, return early to preserve the instance (and its transaction)
        if ($this->pdo !== null) {
            return $this;
        }

        $this->pdo = new PDO($this->dns, $this->username, $this->password, $this->options);
        return $this;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function raw(string $sql): PDOStatement|false
    {
        return $this->pdo->query($sql);
    }

    public function run(string $sql, array $args = []): PDOStatement|false
    {
        if (empty($args)) {
            return $this->pdo->query($sql);
        }

        foreach ($args as $key => $val) {
            if (!str_contains($sql, ":$key")) {
                $this->logger->error("ERROR: Param :$key not found in SQL");
            }
        }
        preg_match_all('/:([a-zA-Z0-9_]+)/', $sql, $matches);
        foreach ($matches[1] as $param) {
            if (!array_key_exists($param, $args)) {
                $this->logger->error("ERROR: SQL expects :$param but it's missing from \$params");
            }
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);

        return $stmt;
    }

    public function rows(
        string $sql,
        array $args = [],
        int $fetchMode = PDO::FETCH_CLASS,
        ?string $class = null
    ): array|false {
        if ($fetchMode === PDO::FETCH_CLASS) {
            return $this->run($sql, $args)->fetchAll($fetchMode, $class);
        }
        return $this->run($sql, $args)->fetchAll($fetchMode);
    }

    public function row(string $sql, string $class, array $args = []): mixed
    {
        return $this->run($sql, $args)->fetchObject($class);
    }

    public function getById(string $table, int $id, int $fetchMode = PDO::FETCH_CLASS, ?string $class = null): mixed
    {
        return $this->run("SELECT * FROM $table WHERE id = ?", [$id])->fetch($fetchMode, $class);
    }

    public function count(string $sql, array $args = []): int
    {
        return $this->run($sql, $args)->rowCount();
    }

    public function lastInsertId(): false|string
    {
        return $this->pdo->lastInsertId();
    }

    public function insert(string $table, array $data): false|string
    {
        // enclose columns in backticks
        $columns = array_map(function ($column) {
            return '`' . trim($column, '`') . '`';
        }, array_keys($data));

        //add columns into comma seperated string
        $columns = implode(',', $columns);

        //get values
        $values = array_values($data);

        $placeholders = array_map(function ($val) {
            return '?';
        }, array_keys($data));

        //convert array into comma seperated string
        $placeholders = implode(',', array_values($placeholders));

        $this->run("INSERT INTO $table ($columns) VALUES ($placeholders)", $values);

        return $this->lastInsertId();
    }

    private function placeholders($count = 0): string
    {
        $result = [];
        if ($count > 0) {
            for ($x = 0; $x < $count; $x++) {
                $result[] = "?";
            }
        }

        return implode(",", $result);
    }

    public function update(string $table, array $data, array $where): int
    {
        //collect the values from data and where
        $values = [];

        //setup fields
        $fieldDetails = null;
        foreach ($data as $key => $value) {
            $key = '`' . trim($key, '`') . '`';
            $fieldDetails .= "$key = ?,";
            $values[] = $value;
        }
        $fieldDetails = rtrim($fieldDetails, ',');

        //setup where
        $whereDetails = null;
        $i = 0;
        foreach ($where as $key => $value) {
            $key = '`' . trim($key, '`') . '`';
            $whereDetails .= $i == 0 ? "$key = ?" : " AND $key = ?";
            $values[] = $value;
            $i++;
        }

        $stmt = $this->run("UPDATE $table SET $fieldDetails WHERE $whereDetails", $values);

        return $stmt->rowCount();
    }

    public function delete(string $table, array $where, int $limit = 1): int
    {
        //collect the values from collection
        $values = array_values($where);

        //setup where
        $whereDetails = null;
        $i = 0;
        foreach ($where as $key => $value) {
            $key = '`' . trim($key, '`') . '`';
            $whereDetails .= $i == 0 ? "$key = ?" : " AND $key = ?";
            $i++;
        }

        //if limit is a number use a limit on the query
        if (is_numeric($limit)) {
            $limit = "LIMIT $limit";
        }

        $stmt = $this->run("DELETE FROM $table WHERE $whereDetails $limit", $values);

        return $stmt->rowCount();
    }

    public function deleteAll(string $table): int
    {
        $stmt = $this->run("DELETE FROM $table");

        return $stmt->rowCount();
    }

    public function deleteById(string $table, int $id): int
    {
        $stmt = $this->run("DELETE FROM $table WHERE id = ?", [$id]);

        return $stmt->rowCount();
    }

    public function deleteByIds(string $table, string $column, string $ids): int
    {
        $stmt = $this->run("DELETE FROM $table WHERE $column IN ($ids)");

        return $stmt->rowCount();
    }

    public function batchInsert(string $table, array $fields, array $data): false|PDOStatement
    {
        if (empty($fields)) {
            throw new InvalidArgumentException("You must pass the table's fields for the batch insert");
        }

        if (empty($data)) {
            throw new InvalidArgumentException("You must pass the data for the batch insert");
        }

        $insert_values = [];
        $question_marks = [];

        foreach ($data as $d) {
            $question_marks[] = '('  . $this->placeholders(sizeof($d)) . ')';
            array_push($insert_values, ...$d);
        }

        $sql = "INSERT INTO $table (" .
            implode(",", $fields) .
            ") " .
            "VALUES " . implode(',', $question_marks);
        $this->logger->debug('batchInsert: ' . $sql . ' with inserted values: ' . implode(', ', $insert_values));
        return $this->run($sql, $insert_values);
    }

    public function startTransaction(): bool
    {
        return true;
    }

    public function commit(): bool
    {
        return true;
    }

    public function rollback(): bool
    {
        return true;
    }
}
