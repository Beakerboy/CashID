<?php

namespace CashID;

/**
 * Overriding the PHP core random number generator
 *
 * There are times when we would like to specify the result of rand() to test edge cases
 */
function rand()
{
    if (RandOverrider::override()) {
        echo "\nGetting fake rand\n";
        return RandOverrider::getRand();
    } else {
        return \rand(100000000, 999999999);
    }
}

class RandOverrider
{
    protected static $override_rand = false;
    protected static $random_values = [];

    public static function setRand(array $rand)
    {
        self::$random_values = $rand;
    }

    public static function getRand()
    {
        return array_shift(self::$random_values);
    }

    public static function setOverride(bool $override)
    {
        echo "\nSetting override to $override\n";
        self::$override_rand = $override;
    }

    public static function override()
    {
        return self::$override_rand;
    }
}
