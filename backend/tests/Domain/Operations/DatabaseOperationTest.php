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

        $expectedPayload = json_encode([
            'entity_id' => $entityId,
            'success' => $success,
            'message' => $message
        ]);

        $this->assertJsonStringEqualsJsonString($expectedPayload, json_encode($dbOp));
    }
}
