<?php
namespace CashID\Tests\CashID;

use CashID\Services\RequestGenerator;
use Paillechat\ApcuSimpleCache\ApcuCache;
use Psr\SimpleCache\CacheInterface;

/**
 * Test the RequestGenerator calss
 *
 * Unit tests for each function
 */
class RequestGeneratorTest extends \PHPUnit\Framework\TestCase
{
    use \phpmock\phpunit\PHPMock;
    /**
     * Test the class constructor
     *
     */
    public function testConstructor()
    {
        $cache = new ApcuCache();
        
        // Create a new object
        $generator = new RequestGenerator("demo.cashid.info", "/api/parse.php", $this->cache);

        // Ensure it is the correct class type
        $this->assertInstanceOf(RequestGenerator::class, $generator);
    }
    
    /**
     * Test the createRequest function
     *
     * @dataProvider dataProviderForTestCreateRequest
     */
    public function testCreateRequest($action, $data, $metadata, $expected)
    {
        $cache = new ApcuCache();

        // Create a RequestGenerator and generate a request given the test data
        $generator = new RequestGenerator("demo.cashid.info", "/api/parse.php", $cache);
        $requestURI = $generator->createRequest($action, $data, $metadata);

        // Remove the unique nonce since we don't know what its value is
        $request_without_nonce = substr($requestURI, 0, -9);

        // Ensure the rest of the request matches the expectation
        $this->assertEquals($expected, $request_without_nonce);

        // Save the nonce
        $nonce = substr($requestURI, -9);

        // Ensure the nonce is a nine digit number
        $this->assertRegExp('/^\d{9}$/', $nonce);
    }

    /**
     * Data for the testCreateRequest function
     */
    public function dataProviderForTestCreateRequest()
    {
        return [
            [ // Test 1
                'login',
                '15366-4133-6141-9638',
                [
                    "optional" => [
                        "position" => ["streetname"],
                    ],
                    "required" => [
                        "contact" => ["social"],
                    ]
                ],
                "cashid:demo.cashid.info/api/parse.php?a=login&d=15366-4133-6141-9638&r=c3&o=p4&x=",
            ],
            [ // Minimal with nulls
                null,
                null,
                null,
                'cashid:demo.cashid.info/api/parse.php?x=',
            ],
            [ // Minimal with empty
                '',
                '',
                [],
                'cashid:demo.cashid.info/api/parse.php?x=',
            ],
        ];
    }

    /**
     * Test the createRequest function with user-initiated action
     *
     * @dataProvider dataProviderForTestCreateUserRequest
     */
    public function testCreateUserRequest($action, $expected)
    {
        $cache = new ApcuCache();
        
        // Create a RequestGenerator and generate a request given the test data
        $generator = new RequestGenerator("demo.cashid.info", "/api/parse.php", $cache);
        $requestURI = $generator->createRequest($action);

        // Ensure the rest of the request matches the expectation
        $this->assertEquals($expected, $requestURI);
    }

    /**
     * Data for the testCreateUserRequest function
     */
    public function dataProviderForTestCreateUserRequest()
    {
        return [
            [
                'logout',
                'cashid:demo.cashid.info/api/parse.php?a=logout',
            ],
        ];
    }

    /**
     * Simulate a storage failure
     *
     */
    public function testStorageFailure()
    {
        // Create a mock request cache with a store that always returns false
        $cache = $this->createMock(CacheInterface::class);
        
        $cache->method('set')->willReturn(false);

        // Create a RequestGenerator with the mocked cache
        $generator = new RequestGenerator("demo.cashid.info", "/api/parse.php", $cache);

        // Generate a request and ensure it fails
        $request = $generator->createRequest();
        
        $this->assertFalse($request);
    }

    /**
     * Verify that the library will check for duplicate nonces and rerun nonce
     * generation until a unique nonce is found.
     *
     * This must run in a separate process as earlier calls to rand() will
     * prevent it from being overloaded.
     *
     * @runInSeparateProcess
     */
    public function testRerunDuplicateNonce()
    {
        $cache = new ApcuCache();

        // Two unique nonce values.
        $exp_nonce1 = 100000000;
        $exp_nonce2 = 999999999;

        // Create a RequestGenerator
        $generator = new RequestGenerator("demo.cashid.info", "/api/parse.php", $cache);

        $rand = $this->getFunctionMock('CashID\Services', "rand");
        $rand->expects($this->exactly(3))->willReturn(100000000, 100000000, 999999999);

        // Generate 2 requests and extract the nonce values.
        $request1 = $generator->createRequest();
        $request2 = $generator->createRequest();

        $nonce1 = substr($request1, -9);
        $nonce2 = substr($request2, -9);

        // Assert that the nonce values are unique despite the rand function
        // returning one nonce value twice.
        $this->assertEquals($exp_nonce1, $nonce1);
        $this->assertEquals($exp_nonce2, $nonce2);
    }
}
