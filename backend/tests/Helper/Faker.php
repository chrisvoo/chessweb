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
            $valueInited = false;
            if ($type->allowsNull()) {
                $value = null;
                $valueInited = true;
            } elseif ($type->isBuiltin()) {
                switch($type->getName()) {
                    case 'string':
                    {
                        $value = str_contains($property->name, 'email') ? 'email@example.it' : "abc";
                        $valueInited = true;
                        break;
                    }
                    case 'int': $value = 123; $valueInited = true; break;
                    case 'float': $value = 123.123; $valueInited = true; break;
                    case 'bool': $value = true; $valueInited = true; break;
                    case 'array': $value = []; $valueInited = true; break;
                    default: echo 'Skipping type: ' . $type->getName();
                }
            }

            if ($valueInited) {
                $instance->{$property->name} = $value;
            }
        }

        return $instance;
    }
}
