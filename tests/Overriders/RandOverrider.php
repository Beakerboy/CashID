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
    public static function getValue(...$params)
    {
        if (self::$override_rand) {
            return array_shift(self::$random_values);
        } else {
            return \rand(...$params);
        }
    }
}
