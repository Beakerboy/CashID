<?php

namespace CashID\Tests\CashID;

use CashID\API;

/**
 * Test the API class
 *
 * Unit tests for each function
 */
class APITest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test the parseRequest() function
     *
     * Ensure the function produces the correct array from the given JSON string.
     *
     * @dataProvider dataProviderForTestParseRequest
     */
    public function testParseRequest(string $request, array $expected_array)
    {
        $result = API::parseRequest($request);
        $this->assertEquals($expected_array, $result);
    }

    /**
     * Data for the testParseRequest test case
     */
    public function dataProviderForTestParseRequest()
    {
        return [
            // Test 1
            [
                'cashid:demo.cashid.info/api/parse.php?a=login&d=15366-4133-6141-9638&r=c3&o=p4&x=95261230581',
                [
                    "parameters" => [
                        "action" => "login",
                        "data" => "15366-4133-6141-9638",
                        "optional" => [
                            'streetname' => '4',
                        ],
                        "required" => [
                            'social' => '3'
                        ],
                        'nonce' => '95261230581',
                    ],
                    'scheme' => 'cashid:',
                    'domain' => 'demo.cashid.info',
                    'path' => '/api/parse.php',
                ],
            ],
        ];
    }
}
