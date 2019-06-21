<?php
namespace CashID;

use CashID\RequestGenerator;

class RequestGeneratorTest extends \PHPUnit\Framework\TestCase
{
    use \phpmock\phpunit\PHPMock;

    /**
     * @testCase testRerunDuplicateNonce
     */
    public function testRerunDuplicateNonce()
    {
        $generator = new RequestGenerator("demo.cashid.info", "/api/parse.php");
        $exp_nonce1 = 100000000;
        $exp_nonce2 = 999999999;
        $rand = $this->getFunctionMock("CashID", "rand");
        $rand->expects($this->exactly(3))->will($this->onConsecutiveCalls($exp_nonce1, $exp_nonce1, $exp_nonce2));
        $request1 = $generator->createRequest();
        $request2 = $generator->createRequest();
        $nonce1 = substr($request1, -9);
        $nonce2 = substr($request2, -9);
        
        $this->assertEquals($exp_nonce1, $nonce1);
        $this->assertEquals($exp_nonce2, $nonce2);
    }
}