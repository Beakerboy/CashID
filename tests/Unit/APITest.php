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
            [ // Missing parameter
                'cashid:demo.cashid.info/api/parse.php?a=login&d=15366-4133-6141-9638&r&x=95261230581',
                [
                    'parameters' => [
                        "action" => "login",
                        "data" => "15366-4133-6141-9638",
                        'required' => [],
                        'nonce' => '95261230581',
                        'optional' => [],
                    ],
                    'scheme' => 'cashid:',
                    'domain' => 'demo.cashid.info',
                    'path' => '/api/parse.php',
                ],
            ],
            [ // Missing parameter family
                'cashid:demo.cashid.info/api/parse.php?a=login&d=15366-4133-6141-9638&r=&x=95261230581',
                [
                    'parameters' => [
                        "action" => "login",
                        "data" => "15366-4133-6141-9638",
                        'required' => [],
                        'nonce' => '95261230581',
                        'optional' => [],
                    ],
                    'scheme' => 'cashid:',
                    'domain' => 'demo.cashid.info',
                    'path' => '/api/parse.php',
                ],
            ],
            [ // Missing parameter type
                'cashid:demo.cashid.info/api/parse.php?a=login&d=15366-4133-6141-9638&r=c&x=95261230581',
                [
                    'parameters' => [
                        "action" => "login",
                        "data" => "15366-4133-6141-9638",
                        'required' => [
                            'contact' => [],
                        ],
                        'nonce' => '95261230581',
                        'optional' => [],
                    ],
                    'scheme' => 'cashid:',
                    'domain' => 'demo.cashid.info',
                    'path' => '/api/parse.php',
                ],
            ],
            [ // Parameter type is undefined
                'cashid:demo.cashid.info/api/parse.php?a=login&d=15366-4133-6141-9638&r=c7&x=95261230581',
                [
                    'parameters' => [
                        "action" => "login",
                        "data" => "15366-4133-6141-9638",
                        'required' => [
                            'contact' => [],
                        ],
                        'nonce' => '95261230581',
                        'optional' => [],
                    ],
                    'scheme' => 'cashid:',
                    'domain' => 'demo.cashid.info',
                    'path' => '/api/parse.php',
                ],
            ],
            [ // Parameters out of order
                'cashid:demo.cashid.info/api/parse.php?a=login&d=15366-4133-6141-9638&r=c54&x=95261230581',
                [
                    'parameters' => [
                        "action" => "login",
                        "data" => "15366-4133-6141-9638",
                        'required' => [
                            'contact' => [
                                'postal' => 4,
                            ],
                        ],
                        'nonce' => '95261230581',
                        'optional' => [],
                    ],
                    'scheme' => 'cashid:',
                    'domain' => 'demo.cashid.info',
                    'path' => '/api/parse.php',
                ],
            ],
            
            [ // Including undefined type
                'cashid:demo.cashid.info/api/parse.php?a=login&d=15366-4133-6141-9638&r=c54z54&x=95261230581',
                [
                    'parameters' => [
                        "action" => "login",
                        "data" => "15366-4133-6141-9638",
                        'required' => [
                            'contact' => [
                                'postal' => 4,
                            ],
                        ],
                        'nonce' => '95261230581',
                        'optional' => [],
                    ],
                    'scheme' => 'cashid:',
                    'domain' => 'demo.cashid.info',
                    'path' => '/api/parse.php',
                ],
            ],
            
            [ // Including undefined type
                'cashid:demo.cashid.info/api/parse.php?a=login&d=15366-4133-6141-9638&r=i78&x=95261230581',
                [
                    'parameters' => [
                        "action" => "login",
                        "data" => "15366-4133-6141-9638",
                        'required' => [
                            'identity' => [
                                'picture' => 8,
                            ],
                        ],
                        'nonce' => '95261230581',
                        'optional' => [],
                    ],
                    'scheme' => 'cashid:',
                    'domain' => 'demo.cashid.info',
                    'path' => '/api/parse.php',
                ],
            ],
        ];
    }
}
