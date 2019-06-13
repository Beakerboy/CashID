<?php

namespace CashID;

function rand()
{
    return array_shift(NonceTest::$randomValues);
}

class NonceTest extends \PHPUnit\Framework\TestCase
{
    public static $randomValues = array();
    public function testSomeRandomness()
    {
        self::$randomValues = [100000000, 100000000, 100000001];
        $this->cashid = new CashID('me.com', '/api/parse.php');
        $request1 = $this->cashid->createRequest();
        $request2 = $this->cashid->createRequest();
        $nonce1 = substr($request1, -9);
        $nonce2 = substr($request2, -9);
        
        $this->assertEquals(100000000, $nonce1);
        $this->assertEquals(100000001, $nonce2);
    }
}
