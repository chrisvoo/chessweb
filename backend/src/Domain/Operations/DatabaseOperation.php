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
        return [
            'entity_id' => $this->entityId,
            'success' => $this->success,
            'message' => $this->message,
            'affected_rows' => $this->affectedRows
        ];
    }
}
