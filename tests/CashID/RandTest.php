<?php
namespace CashID;

use CashID\RequestGenerator;

class RequestGeneratorTest extends \PHPUnit\Framework\TestCase
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
        $rand = $this->getFunctionMock(__NAMESPACE__, "rand");
        $rand->expects($this->any())->with(100000000, 999999999)->will($this->onConsecutiveCalls(100000000, 100000000, 100000000, 999999999));
        $request1 = $generator->createRequest();
        $request2 = $generator->createRequest();
        $nonce1 = substr($request1, -9);
        $nonce2 = substr($request2, -9);
        
        $this->assertEquals($exp_nonce1, $nonce1);
        $this->assertEquals($exp_nonce2, $nonce2);
    }
}
