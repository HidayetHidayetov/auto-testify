<?php

namespace Hidayetov\AutoTestify\Helpers;

class TestUtils
{
    public static function snakeCase($value)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }

    public static function camelCase($value)
    {
        return lcfirst($value);
    }
}