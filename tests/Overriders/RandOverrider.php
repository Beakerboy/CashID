<?php

namespace CashID;

/**
 * Overriding the PHP core random number generator
 *
 * There are times when we would like to specify the result of rand() to test edge cases
 */
function rand()
{
    return RandOverrider::getRand();
}

class RandOverrider
{
    protected static $override_rand = false;
    protected static $random_values = [];

    public static function setRand(array $rand)
    {
        echo "\nSetting the Rand array\n";
        self::$random_values = $rand;
    }

    public static function getRand()
    {
        if (self::$override_rand === true) {
            return array_shift(self::$random_values);
        } else {
            return \rand(100000000, 999999999);
        }
    }

    public static function setOverride()
    {
        self::$override_rand = true;
    }

    public static function unsetOverride()
    {
        self::$override_rand = false;
    }
}
