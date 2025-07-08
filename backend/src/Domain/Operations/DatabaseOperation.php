<?php

namespace App\Domain\Operations;

use JsonSerializable;

class DatabaseOperation implements JsonSerializable
{
    public const ENTITY_CREATED = 1;
    public const ENTITY_UPDATED = 2;
    public const ENTITY_DELETED = 3;
    public const NOTHING_TO_DO = 4;
    // as convention, all error codes must be greater than 100
    public const ENTITY_DUPLICATED = 100;
    public const SERVER_ERROR = 101;
    public const ENTITY_NOT_FOUND = 102;

    public ?int $entityId;
    public bool $success;
    public string $message;
    public int $code;
    public int $affectedRows;

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return array
     */
    public function jsonSerialize(): array
    {
        $fields = [
            'success' => $this->success,
            'message' => $this->message,
            'code' => $this->code
        ];

        if (isset($this->affectedRows) && $this->success) {
            $fields['affected_rows'] = $this->affectedRows;
        }

        if (isset($this->entityId)) {
            $fields['entity_id'] = $this->entityId;
        }

        return $fields;
    }

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public static function newEntityOperation(
        string $message,
        int $code,
        ?int $entityId = null,
        int $affectedRows = 0
    ): DatabaseOperation {
        $dbOp = new DatabaseOperation();
        $dbOp->entityId = $entityId;
        $dbOp->success = $code < self::ENTITY_DUPLICATED;
        $dbOp->message = $message;
        $dbOp->code = $code;
        $dbOp->affectedRows = $affectedRows;
        return $dbOp;
    }

    public static function failed(string $message, int $code): DatabaseOperation
    {
        return self::newEntityOperation($message, $code);
    }

    public static function newSingleEntitySuccessfullyCreated(int $entityId): DatabaseOperation
    {
        return self::newEntityOperation('Entity created', self::ENTITY_CREATED, $entityId, 1);
    }

    public static function newSingleEntitySuccessfullyUpdated(int $entityId): DatabaseOperation
    {
        return self::newEntityOperation('Entity updated', self::ENTITY_UPDATED, $entityId, 1);
    }

    public static function newSingleEntitySuccessfullyDeleted(int $entityId): DatabaseOperation
    {
        return self::newEntityOperation('Entity deleted', self::ENTITY_DELETED, $entityId, 1);
    }
}
