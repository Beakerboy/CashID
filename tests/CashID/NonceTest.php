<?php

namespace CashID;

/**
 * Overriding the PHP core random number generator
 *
 * There are times when we would like to specify the result of rand() to test edge cases
 */
function rand()
{
    $rand = array_shift(NonceTest::$randomValues);
    NonceTest::$randomValues[] = $rand + 1;
    return $rand ?? random_int(100000000, 999999999);
}

/**
 * Overriding the PHP core time function
 *
 * There are times when we would like to simulate the passage of time
 */
function time()
{
    if (NonceTest::$time_override) {
        NonceTest::$time_override = false;
        return NonceTest::$time_value
    } else {
        return strtotime();
    }
}

class NonceTest extends \PHPUnit\Framework\TestCase
{
    public static $randomValues = array();
    public static $time_override = false;
    public static $time_value = 0;
    
    public static function overrideTime($time)
    {
        self::$time_override = true;
        self::$time_value = $time;
    }

    /**
     * @testCase testRerunDuplicateNonce
     */
    public function testRerunDuplicateNonce()
    {
        self::$randomValues = [100000000, 100000000];
        $this->generator = new RequestGenerator('me.com', '/api/parse.php');
        $request1 = $this->generator->createRequest();
        $request2 = $this->generator->createRequest();
        $nonce1 = substr($request1, -9);
        $nonce2 = substr($request2, -9);
        
        $this->assertEquals(100000000, $nonce1);
        $this->assertEquals(100000001, $nonce2);
    }
}
