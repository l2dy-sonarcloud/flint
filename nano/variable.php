<?php

class Nano_Variable
{
    private static $_data = array();

    public static function get($name)
    {
        if (isset (self::$_data[$name])) {
            return self::$_data[$name];
        }

        return false;
    }

    public static function remove($name)
    {
        unset(self::$_data[$name]);
    }

    public static function set($name, $value)
    {
        self::$_data[$name] = $value;
    }
}
