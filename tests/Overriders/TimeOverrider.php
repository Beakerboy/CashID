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
    return TimeOverrider::getValue();
}

class TimeOverrider extends Overrider
{
    protected static $override;
    protected static $values;
    
    public static function getValue()
    {
        if (self::$override) {
            return array_shift(self::$values);
        } else {
            return \time();
        }
    }
}
