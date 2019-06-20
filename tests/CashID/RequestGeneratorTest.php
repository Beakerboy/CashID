<?php
namespace CashID\Tests\CashID;

use CashID\APCuStoreOverrider;
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
    public function testCreateRequest($action, $data, $metadata, $expected)
    {
        $requestURI = $this->generator->createRequest($action, $data, $metadata);

        // Remove the unique nonce
        $request_without_nonce = substr($requestURI, 0, -9);
        $this->assertEquals($expected, $request_without_nonce);
        
        $nonce = substr($requestURI, -9);

        // The nonce is a nine digit number
        $this->assertRegExp('/^\d{9}$/', $nonce);
    }

    public function dataProviderForTestCreateRequest()
    {
        return [
            [ // Test 1
                [
                    'login',
                    '15366-4133-6141-9638',
                    [
                        "optional" => [
                            "position"=> ["streetname"],
                        ],
                        "required" => [
                            "contact"=> ["social"],
                        ]
                    ],
                    "cashid:demo.cashid.info/api/parse.php?a=login&d=15366-4133-6141-9638&r=c3&o=p4&x=",
                ],
            ],
        ];
    }

    /**
     * @testCase testRerunDuplicateNonce
     * @runInSeparateProcess
     */
    public function testRerunDuplicateNonce()
    {
        RandOverrider::setOverride();
        RandOverrider::setValues([100000000, 100000000, 100000001]);
        $request1 = $this->generator->createRequest();
        $request2 = $this->generator->createRequest();
        RandOverrider::unsetOverride();
        $nonce1 = substr($request1, -9);
        $nonce2 = substr($request2, -9);
        
        $this->assertEquals(100000000, $nonce1);
        $this->assertEquals(100000001, $nonce2);
    }

    /**
     * @testCase testStorageFailure
     * @runInSeparateProcess
     */
    public function testStorageFailure()
    {
        APCuStoreOverrider::setOverride();
        APCuStoreOverrider::setValues([false]);
        $request = $this->generator->createRequest();
        APCuStoreOverrider::unsetOverride();
        
        $this->assertFalse($request);
    }
}
