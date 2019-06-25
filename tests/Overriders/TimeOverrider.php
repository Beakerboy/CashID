<?php

namespace CashID;

use \CoreOverrrider\OverriderBase;

/**
 * Overriding the PHP core time function
 *
 * There are times when we would like to simulate the passage of time
 */
function time()
{
    return TimeOverrider::getValue();
}

class TimeOverrider extends OverriderBase
{

    protected static $values;
    protected static $override;
    public static function getValue()
    {
        if (self::$override) {
            return array_shift(self::$values);
        } else {
            return \time();
        }
    }
}
