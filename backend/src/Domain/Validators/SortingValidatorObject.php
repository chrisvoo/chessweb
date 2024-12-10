<?php

namespace App\Domain\Validators;

use App\Domain\Pagination\SortDirection;
use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

class SortingValidatorObject implements ValidatorObjectInterface
{
    public function __construct(private readonly array $allowedFields) {}

    public function getValidator(): Validatable
    {
        return v::key(
            'sort_by',
            v::in($this->allowedFields, true)->notEmpty(),
            false
        )->key(
            'sort_order',
            v::in([SortDirection::ASC->value, SortDirection::DESC->value])->notEmpty(),
            false,
        );
    }
}
