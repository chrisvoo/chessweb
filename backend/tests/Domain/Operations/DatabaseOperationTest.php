<?php

declare(strict_types=1);

namespace Tests\Domain\Operations;

use App\Domain\Operations\DatabaseOperation;
use Tests\TestCase;

class DatabaseOperationTest extends TestCase
{
    public function testJsonSerialize()
    {
        $entityId = 1;
        $success = true;
        $message = 'Success';

        $dbOp = new DatabaseOperation();
        $dbOp->entityId = $entityId;
        $dbOp->success = $success;
        $dbOp->message = $message;
        $dbOp->code = DatabaseOperation::ENTITY_UPDATED;
        $dbOp->affectedRows = 1;

        $expectedPayload = json_encode([
            'entity_id' => $entityId,
            'success' => $success,
            'message' => $message,
            'affected_rows' => 1,
            'code' => DatabaseOperation::ENTITY_UPDATED
        ]);

        $this->assertJsonStringEqualsJsonString($expectedPayload, json_encode($dbOp));
    }
}
