<?php

namespace App\Infrastructure\Persistence;

use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;

class DatabaseManager implements DatabaseManagerInterface
{
    private PDO $pdo;

    public function __construct(
        private string $dns,
        private string $username,
        private string $password,
        private readonly LoggerInterface $logger,
        private array $options = [],
    ) {
    }

    public function connect(): DatabaseManagerInterface
    {
        $this->pdo = new PDO($this->dns, $this->username, $this->password, $this->options);
        return $this;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object
     *
     * @param  string $sql       sql query
     * @return PDOStatement|false returns a PDOStatement object, or FALSE on failure.
     */
    public function raw(string $sql): PDOStatement|false
    {
        return $this->pdo->query($sql);
    }

    /**
     * Run sql query
     *
     * @param  string $sql       sql query
     * @param  array  $args      params
     * @return PDOStatement|false returns a PDOStatement object, or FALSE on failure.
     */
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

    /**
     * Returns an array containing all of the result set rows
     * @param string $sql
     * @param array $args
     * @param int $fetchMode
     * @param string|null $class
     * @return array|false
     */
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

    /**
     * Fetches the next row from a result set
     *
     * @param string $sql
     * @param array $args
     * @param string $class
     * @return mixed returns single record
     */
    public function row(string $sql, string $class, array $args = []): mixed
    {
        return $this->run($sql, $args)->fetchObject($class);
    }

    /**
     * @param string $table
     * @param int $id
     * @param int $fetchMode
     * @param string|null $class
     * @return mixed
     */
    public function getById(string $table, int $id, int $fetchMode = PDO::FETCH_CLASS, ?string $class = null): mixed
    {
        return $this->run("SELECT * FROM $table WHERE id = ?", [$id])->fetch($fetchMode, $class);
    }

    /**
     * Returns the number of rows affected by the last SQL statement
     * @param string $sql
     * @param array $args
     * @return int
     */
    public function count(string $sql, array $args = []): int
    {
        return $this->run($sql, $args)->rowCount();
    }

    /**
     * Returns the ID of the last inserted row or sequence value
     */
    public function lastInsertId(): false|string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * insert record
     *
     * @param  string $table table name
     * @param  array $data  array of columns and values
     * @return false|string Returns the ID of the last inserted row or sequence value
     */
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

    /**
     * update record
     *
     * @param  string $table table name
     * @param  array $data  array of columns and values
     * @param  array $where array of columns and values
     * @return int Returns the number of rows affected by the last SQL statement
     */
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

    /**
     * Delete records
     *
     * @param  string $table table name
     * @param  array $where array of columns and values
     * @param  integer $limit limit number of records
     * @return int Returns the number of rows affected by the last SQL statement
     */
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

    /**
     * Delete all records records
     *
     * @param  string $table table name
     * @return int Returns the number of rows affected by the last SQL statement
     */
    public function deleteAll(string $table): int
    {
        $stmt = $this->run("DELETE FROM $table");

        return $stmt->rowCount();
    }

    /**
     * Delete record by id
     *
     * @param  string $table table name
     * @param  integer $id id of record
     * @return int Returns the number of rows affected by the last SQL statement
     */
    public function deleteById(string $table, int $id): int
    {
        $stmt = $this->run("DELETE FROM $table WHERE id = ?", [$id]);

        return $stmt->rowCount();
    }

    /**
     * Delete record by ids
     *
     * @param  string $table table name
     * @param  string $column name of column
     * @param  string $ids ids of records
     * @return int Returns the number of rows affected by the last SQL statement
     */
    public function deleteByIds(string $table, string $column, string $ids): int
    {
        $stmt = $this->run("DELETE FROM $table WHERE $column IN ($ids)");

        return $stmt->rowCount();
    }
}
