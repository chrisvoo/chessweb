<?php

namespace Tests\Domain\Pagination;

use App\Domain\Pagination\SortDirection;
use Exception;
use PHPUnit\Framework\TestCase;

class SortDirectionTest extends TestCase
{
    public function testAscValue(): void
    {
        $this->assertEquals('asc', SortDirection::ASC->value);
    }

    public function testDescValue(): void
    {
        $this->assertEquals('desc', SortDirection::DESC->value);
    }

    public function testFromValueAsc(): void
    {
        $direction = SortDirection::fromValue('asc');
        $this->assertSame(SortDirection::ASC, $direction);
    }

    public function testFromValueDesc(): void
    {
        $direction = SortDirection::fromValue('desc');
        $this->assertSame(SortDirection::DESC, $direction);
    }

    public function testFromValueInvalid(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unexpected match value');

        SortDirection::fromValue('invalid');
    }

    public function testEnumCases(): void
    {
        $cases = SortDirection::cases();

        $this->assertCount(2, $cases);
        $this->assertContains(SortDirection::ASC, $cases);
        $this->assertContains(SortDirection::DESC, $cases);
    }
}
