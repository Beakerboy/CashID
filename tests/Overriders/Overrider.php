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
 *         if (static::$override_rand) {
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
        static::$values = $values;
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
        static::$override = true;
    }

    /**
     * Turn Overriding off
     */
    public static function unsetOverride()
    {
        static::$override = false;
    }

    public static function createMock($namespace, $function)
    {
        $name = ucfirst($function);
        $filename = $name . "Overrider.php";
        $file_contents = "<?php

namespace {$namespace};

use Overrider;

function {$function}(...\$params)
{
    return {$name}Overrider::getValue(...\$params);
}

class {$name}Overrider extends Overrider
{
    protected static \$values;
    protected static \$override;
    public static function getValue(...\$params)
    {
        if (self::\$override) {
            return array_shift(self::\$values);
        } else {
            return \\{$function}(...\$params);
        }
    }
}
";
    file_put_contents($filename, $file_contents);
    include($filename);
    }
}
