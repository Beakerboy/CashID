<?php

namespace CashID;

class RandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @testCase testRerunDuplicateNonce
     */
    public function testRerunDuplicateNonce()
    {
        $exp_nonce1 = \rand(100000000, 999999999);
        $exp_nonce2 = \rand(100000000, 999999999);
        \CoreOverrider\OverriderBase::createMock("CashID", "rand");
        $generator = new RequestGenerator("demo.cashid.info", "/api/parse.php");
        RandOverrider::setOverride();
        RandOverrider::willReturn($exp_nonce1, $exp_nonce1, $exp_nonce2);
        $request1 = $generator->createRequest();
        $request2 = $generator->createRequest();
        RandOverrider::unsetOverride();
        $nonce1 = substr($request1, -9);
        $nonce2 = substr($request2, -9);
        
        $this->assertEquals($exp_nonce1, $nonce1);
        $this->assertEquals($exp_nonce2, $nonce2);
    }
}
