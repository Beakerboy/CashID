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
        return RandOverrider::getRand();
    } else {
        return random_int(100000000, 999999999);
    }
}
class RandOverrider
{
    protected static $override_rand = false;
    protected static $randomValues = [];

    public function setRand(array $rand)
    {
        self::$random_values = $rand;
    }

    public static function getRand()
    {
        return array_shift(self::$random_values);
    }

    public function setOverride(boolean $override)
    {
        self::$override_rand = $override;
    }

    public static function override()
    {
        return self::$override_rand;
    }
}
