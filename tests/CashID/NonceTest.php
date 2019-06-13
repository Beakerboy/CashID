<?php

namespace CashID;

function rand()
{
    $rand = array_shift(NonceTest::$randomValues);
    NonceTest::$randomValues[] = $rand + 1;
    return $rand ?? random_int(100000000, 999999999);
}

class NonceTest extends \PHPUnit\Framework\TestCase
{
    public static $randomValues = array();
    
    /**
     * @testCase testRerunDuplicateNonce
     */
    public function testRerunDuplicateNonce()
    {
        self::$randomValues = [100000000, 100000000];
        $this->cashid = new CashID('me.com', '/api/parse.php');
        $request1 = $this->cashid->createRequest();
        $request2 = $this->cashid->createRequest();
        $nonce1 = substr($request1, -9);
        $nonce2 = substr($request2, -9);
        
        $this->assertEquals(100000000, $nonce1);
        $this->assertEquals(100000001, $nonce2);
    }
}
