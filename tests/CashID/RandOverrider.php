<?php
namespace CashID;
/**
 * Overriding the PHP core random number generator
 *
 * There are times when we would like to specify the result of rand() to test edge cases
 */
function rand()
{
    if (RandOverrider::$override_rand) {
        return array_shift(NonceTest::$randomValues);
    } else {
        return random_int(100000000, 999999999);
    }
}
class RandOverrider
{
    public static $override_rand = false;
    public static $randomValues = [];

    public function setRand(array $rand)
    {
        self::$random_values = $rand;
    }

    public function setOverride(boolean $override)
    {
        self::$override_rand = $override;
    }
}
