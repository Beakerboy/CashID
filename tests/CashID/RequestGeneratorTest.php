<?php
namespace CashID\Tests\CashID;

use CashID\RandOverrider;
use CashID\RequestGenerator;

class RequestGeneratorTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->generator = new RequestGenerator("demo.cashid.info", "/api/parse.php");
    }

    /**
     * @testCase constructor
     */
    public function testConstructor()
    {
        $this->assertInstanceOf(RequestGenerator::class, $this->generator);
    }
    
    /**
     * @testCase Create Request
     */
    public function testCreateRequest()
    {
        $metadata = [
            "optional" => [
                "position"=> ["streetname"],
            ],
            "required" => [
                "contact"=> ["social"],
            ],
        ];
        $requestURI = $this->generator->createRequest("login", "15366-4133-6141-9638", $metadata);
        $this->assertEquals("cashid:demo.cashid.info/api/parse.php?a=login&d=15366-4133-6141-9638&r=c3&o=p4&x=", substr($requestURI, 0, -9));
        $nonce = substr($requestURI, -9);
        $this->assertRegExp('/^\d{9}$/', $nonce);
    }

    /**
     * @testCase testRerunDuplicateNonce
     */
    public function testRerunDuplicateNonce()
    {
        RandOverrider::setOverride(true);
        RandOverrider::setRand([100000000, 100000000, 100000001]);
        $request1 = $this->generator->createRequest();
        $request2 = $this->generator->createRequest();
        RandOverrider::setOverride(false);
        $nonce1 = substr($request1, -9);
        $nonce2 = substr($request2, -9);
        
        $this->assertEquals(100000000, $nonce1);
        $this->assertEquals(100000001, $nonce2);
    }
}
