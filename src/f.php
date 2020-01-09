<?php


namespace rikmeijer\functional;


class f
{

    private static $functions = [];

    static function __callStatic($name, $arguments)
    {
        if (array_key_exists($name, self::$functions)) {
            return (self::$functions[$name])(...$arguments);
        }
        self::$functions[$name] = $arguments[0];
    }

}