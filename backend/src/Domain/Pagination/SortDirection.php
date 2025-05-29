<?php

namespace App\Domain\Pagination;

enum SortDirection: string
{
    case ASC = 'asc';
    case DESC = 'desc';

    public static function fromValue(string $direction): SortDirection
    {
        return match ($direction) {
            self::ASC->value => self::ASC,
            self::DESC->value => self::DESC,
            default => throw new \Exception('Unexpected match value'),
        };
    }
}
