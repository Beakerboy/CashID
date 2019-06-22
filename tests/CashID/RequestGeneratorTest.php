<?php
namespace CashID\Tests\CashID;

use CashID\RandOverrider;
use CashID\RequestCacheInterface;
use CashID\RequestGenerator;
use CashID\TimeOverrider;

class RequestGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @testCase constructor
     */
    public function testConstructor()
    {
        $this->generator = new RequestGenerator("demo.cashid.info", "/api/parse.php");
        $this->assertInstanceOf(RequestGenerator::class, $this->generator);
    }
    
    /**
     * @testCase Create Request
     * @dataProvider dataProviderForTestCreateRequest
     */
    public function testCreateRequest($action, $data, $metadata, $expected)
    {
        $generator = new RequestGenerator("demo.cashid.info", "/api/parse.php");
        $requestURI = $generator->createRequest($action, $data, $metadata);

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
        ];
    }

    /**
     * @testCase testStorageFailure
     */
    public function testStorageFailure()
    {
        $cache = $this->createMock(RequestCacheInterface::class);
        
        $cache->method('store')->willReturn(false);
        
        $generator = new RequestGenerator("demo.cashid.info", "/api/parse.php", $cache);
        $request = $generator->createRequest();
        
        $this->assertFalse($request);
    }
}
