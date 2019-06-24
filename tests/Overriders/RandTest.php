<?php

namespace CashID;

use CashID\RequestGenerator;

class RandTest extends \PHPUnit\Framework\TestCase
{
    use \phpmock\phpunit\PHPMock;

    /**
     * @testCase testRerunDuplicateNonce
     * @runInSeparateProcess
     */
    public function testRerunDuplicateNonce()
    {
        $generator = new RequestGenerator("demo.cashid.info", "/api/parse.php");
        $exp_nonce1 = 100000000;
        $exp_nonce2 = 999999999;
        $rand = $this->getFunctionMock("CashID", "rand");
        $rand->expects($this->any())->will($this->onConsecutiveCalls(100000000, 100000001, 100000002, 100000003, 100000004, 100000005, 999999999));
        $mock = $rand->build();
        $request1 = $generator->createRequest();
        $request2 = $generator->createRequest();
        $nonce1 = substr($request1, -9);
        $nonce2 = substr($request2, -9);
        
        $this->assertEquals($exp_nonce1, $nonce1);
        $this->assertEquals($exp_nonce2, $nonce2);
    }
}
