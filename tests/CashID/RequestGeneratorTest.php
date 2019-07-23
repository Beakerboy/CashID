<?php
namespace CashID\Tests\CashID;

use CashID\Cache\RequestCacheInterface;
use CashID\RequestGenerator;

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

    /**
     * Verify that the library will check for duplicate nonces
     * and rerun nonce generation until a unique nonce is found.
     *
     * @testCase testRerunDuplicateNonce
     * @runInSeparateProcess
     */
    public function testRerunDuplicateNonce()
    {
        // Generate two random nonce values.
        // (should I specify them instead to ensure uniqueness?)
        $exp_nonce1 = \rand(100000000, 999999999);
        $exp_nonce2 = \rand(100000000, 999999999);

        // Override the rand function with a mock.
        \CoreOverrider\OverriderBase::createMock("CashID", "rand");

        // Create a RequestGenerator
        $generator = new RequestGenerator("demo.cashid.info", "/api/parse.php");

        // Turn on overriding and set the values to return instead.
        \CashID\RandOverrider::setOverride();
        \CashID\RandOverrider::willReturn($exp_nonce1, $exp_nonce1, $exp_nonce2);

        // Generate 2 requests and extract the nonce values.
        $request1 = $generator->createRequest();
        $request2 = $generator->createRequest();
        \CashID\RandOverrider::unsetOverride();
        $nonce1 = substr($request1, -9);
        $nonce2 = substr($request2, -9);

        // Assert that the nonce values are unique despite the rand function
        // returning one nonce value twice.
        $this->assertEquals($exp_nonce1, $nonce1);
        $this->assertEquals($exp_nonce2, $nonce2);
    }
}
