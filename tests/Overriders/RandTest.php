<?php

namespace CashID;

class RandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @testCase testRerunDuplicateNonce
     */
    public function testRerunDuplicateNonce()
    {
        \CoreOverrider\OverriderBase::createMock("CashID", "rand");
        $generator = new RequestGenerator("demo.cashid.info", "/api/parse.php");
        RandOverrider::setOverride();
        RandOverrider::setValues([100000000, 100000000, 100000001]);
        $request1 = $generator->createRequest();
        $request2 = $generator->createRequest();
        RandOverrider::unsetOverride();
        $nonce1 = substr($request1, -9);
        $nonce2 = substr($request2, -9);
        
        $this->assertEquals(100000000, $nonce1);
        $this->assertEquals(100000001, $nonce2);
    }
}
