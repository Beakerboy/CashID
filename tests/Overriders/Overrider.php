<?php

namespace CashID;

/**
 * Override a core function within a namespace
 *
 * Add a replacement function definition outside the class.
 * Example:
 * function rand(...$params)
 * {
 *     return RandOverrider::getValue(...$params);
 * }
 *
 * Extend this class and write an appropriate getValue() function.
 * A simple example is:
 *
 *     public static function getValue(...$params)
 *     {
 *         if (self::$override_rand) {
 *             return array_shift(self::$random_values);
 *         } else {
 *             return \rand(...$params);
 *         }
 *     }
 */

abstract class Overrider
{
    protected static $override = false;
    protected static $values = [];

    /**
     * Assign the list of values to be generated
     */
    public static function setValues(array $values)
    {
        self::$values = $values;
    }

    /**
     * Get the next value if the function is overridden
     */
    abstract public static function getValue();

    /**
     * Turn overriding on
     */
    public static function setOverride()
    {
        self::$override = true;
    }

    /**
     * Turn Overriding off
     */
    public static function unsetOverride()
    {
        self::$override = false;
    }
}
