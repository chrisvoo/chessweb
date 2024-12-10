<?php

namespace App\Domain\Validators;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;

class PaginationValidatorObject implements ValidatorObjectInterface
{
    public function getValidator(): Validatable
    {
        return v::key(
            'page',
            v::intVal()->min(1)->notEmpty(),
            false
        )->key(
            'page_size',
            v::intVal()->min(1)->notEmpty(),
            false
        );
    }
}
