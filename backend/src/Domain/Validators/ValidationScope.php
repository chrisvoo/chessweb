<?php

namespace App\Domain\Validators;

enum ValidationScope
{
    case CREATE;
    case UPDATE;

    case TAGS;
    case CATEGORIES;
}
