<?php

namespace CashID;

use CashID\Overrider;

/**
 * Overriding the PHP core random number generator
 *
 * There are times when we would like to specify the result of rand() to test edge cases
 */
function rand(...$params)
{
    return RandOverrider::getValue(...$params);
}

class RandOverrider extends Overrider
{
    protected static $override;
    protected static $values;
    
    public static function getValue(...$params)
    {
        if (self::$override) {
            return array_shift(self::$values);
        } else {
            return \rand(...$params);
        }
    }
}
