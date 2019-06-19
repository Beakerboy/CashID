<?php

namespace CashID;

use CashID\Overrider;

/**
 * Overriding the PHP core random number generator
 *
 * There are times when we would like to specify the result of rand() to test edge cases
 */
function apcu_store(...$params)
{
    return APCuStoreOverrider::getValue(...$params);
}

class APCuStoreOverrider extends Overrider
{
    public static function getValue(...$params)
    {
        if (self::$override) {
            $return_value = array_shift(self::$values);
            if ($return_value) {
                return \apcu_store(...$params);
            }
            return false;
        } else {
            return \apcu_store(...$params);
        }
    }
}
