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
    public static function getValue()
    {
        if (self::$override) {
            return array_shift(self::$values);
        } else {
            return \time();
        }
    }
}
