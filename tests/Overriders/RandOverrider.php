<?php

namespace CashID;

/**
 * Overriding the PHP core random number generator
 *
 * There are times when we would like to specify the result of rand() to test edge cases
 */
public function rand(...$params)
{
    return RandOverrider::getRand(...$params);
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

    public static function getRand(...$params)
    {
        if (self::$override_rand === true) {
            return array_shift(self::$random_values);
        } else {
            return \rand(...$params);
        }
    }

    public static function setOverride()
    {
        echo "\nOverrideing rand()\n";
        self::$override_rand = true;
    }

    public static function unsetOverride()
    {
        echo "\nTurn off rand() override\n";
        self::$override_rand = false;
    }
}
