<?php

namespace App\Domain\Operations;

use JsonSerializable;

class DatabaseOperation implements JsonSerializable
{
    public const ENTITY_CREATED = 1;
    public const ENTITY_UPDATED = 2;
    public const ENTITY_DELETED = 3;

    public int $entityId;
    public bool $success;
    public string $message;
    public int $code;
    public int $affectedRows;

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize(): array
    {
        $fields = [
            'entity_id' => $this->entityId,
            'success' => $this->success,
            'message' => $this->message,
            'code' => $this->code
        ];

        if (isset($this->affectedRows)) {
            $fields['affected_rows'] = $this->affectedRows;
        }

        return $fields;
    }

    private static function newEntityOperation(int $entityId, string $message, int $code, int $affectedRows = 1): DatabaseOperation
    {
        $dbOp = new DatabaseOperation();
        $dbOp->entityId = $entityId;
        $dbOp->success = true;
        $dbOp->message = $message;
        $dbOp->code = $code;
        $dbOp->affectedRows = $affectedRows;
        return $dbOp;
    }

    public static function newSingleEntitySuccessfullyCreated(int $entityId): DatabaseOperation
    {
        return self::newEntityOperation($entityId, 'Entity created', self::ENTITY_CREATED);
    }

    public static function newSingleEntitySuccessfullyUpdated(int $entityId): DatabaseOperation
    {
        return self::newEntityOperation($entityId, 'Entity updated', self::ENTITY_UPDATED);
    }

    public static function newSingleEntitySuccessfullyDeleted(int $entityId): DatabaseOperation
    {
        return self::newEntityOperation($entityId, 'Entity deleted', self::ENTITY_DELETED);
    }
}
