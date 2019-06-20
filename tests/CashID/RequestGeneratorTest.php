<?php
namespace CashID\Tests\CashID;

use CashID\RandOverrider;
use CashID\RequestCacheInterface;
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
     * @testCase testRerunDuplicateNonce
     */
    public function testRerunDuplicateNonce()
    {
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

    /**
     * @testCase testStorageFailure
     */
    public function testStorageFailure()
    {
        RandOverrider::setOverride();
        $mocked_nonce = 200000000;
        RandOverrider::setValues([$mocked_nonce]);

        TimeOverrider::setOverride();
        $mocked_time = 123456789;
        TimeOverrider::setValues([$mocked_time]);
        $cache = $this->createMock(RequestCacheInterface::class);
        
        $expected_key = "cashid_request_{$mocked_nonce}";
        $request_uri = "cashid:demo.cashid.info/api/parse.php?x={$mocked_nonce}";
        $cached_array = [ 'available' => true, 'request' => $request_uri, 'expires' => $mocked_time + (60 * 15) ];
        $cache->method('store')->with($expected_key, $cached_array)->willReturn(false);
        $generator = new RequestGenerator("demo.cashid.info", "/api/parse.php", $cache);
        $request = $this->generator->createRequest();
        RandOverrider::unsetOverride();
        TimeOverrider::unsetOverride();
        
        $this->assertFalse($request);
    }
}
