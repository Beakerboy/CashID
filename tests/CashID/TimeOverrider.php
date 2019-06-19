<?php

namespace CashID;

/**
 * Overriding the PHP core time function
 *
 * There are times when we would like to simulate the passage of time
 */
function time()
{
    if (TimeOverrider::$time_override) {
        TimeOverrider::$time_override = false;
        return TimeOverrider::$time_value;
    } else {
        return strtotime("now");
    }
}

class TimeOverrider
{
    public static $time_override = false;
    public static $time_value = 0;
    
    public static function overrideTime($time)
    {
        self::$time_override = true;
        self::$time_value = $time;
    }
}
