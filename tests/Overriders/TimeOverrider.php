<?php

namespace CashID;

use CashID\Overrrider;

/**
 * Overriding the PHP core time function
 *
 * There are times when we would like to simulate the passage of time
 */
function time()
{
    return RandOverrider::getValue();
}
class TimeOverrider extends Overrider
{
    protected static $override = false;
    protected static $values = [];
    
    public static function setValues(array $values)
    {
        self::$values = $values;
    }
    public static function getValue()
    {
        if (self::$override) {
            return array_shift(self::$values);
        } else {
            return \time();
        }
    }
    public static function setOverride()
    {
        self::$override = true;
    }
    public static function unsetOverride()
    {
        self::$override = false;
    }
}
