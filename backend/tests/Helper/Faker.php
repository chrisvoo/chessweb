<?php

namespace Tests\Helper;

use Exception;
use ReflectionClass;
use ReflectionProperty;

class Faker
{
    /**
     * @throws \ReflectionException if the class does not exist.
     * @throws Exception if at least one property of the class hasn't a type
     */
    public static function fakeData(string $class): mixed
    {
        $classObj = new ReflectionClass($class);
        $properties = $classObj->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);

        $instance = new $class();
        foreach ($properties as $property) {
            if (!$property->hasType()) {
                throw new Exception("$property->name of $class hasn't a type!");
            }
            $type = $property->getType();
            if ($type->allowsNull()) {
                $value = null;
            } elseif ($type->isBuiltin()) {
                switch($type->getName()) {
                    case 'string': $value = "abc"; break;
                    case 'int': $value = 123; break;
                    case 'float': $value = 123.123; break;
                    case 'bool': $value = true; break;
                    case 'array': $value = []; break;
                    default: echo 'Skipping type: ' . $type->getName();
                }
            }

            $instance->{$property->name} = $value;
        }

        return $instance;
    }
}
