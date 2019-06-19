<?php

namespace CashID;

class Overrider
{
    protected static $override = false;
    protected static $values = [];
    
    public static function setValues(array $values)
    {
        self::$values = $values;
    }

    abstract public static function getValue();

    public static function setOverride()
    {
        self::$override = true;
    }

    public static function unsetOverride()
    {
        self::$override = false;
    }
}
