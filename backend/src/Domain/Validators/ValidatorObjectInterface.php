<?php

namespace App\Domain\Validators;

use Respect\Validation\Validatable;

interface ValidatorObjectInterface
{
    public function getValidator(): Validatable;
}
