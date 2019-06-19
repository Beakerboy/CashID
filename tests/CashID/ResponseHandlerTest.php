<?php

namespace CashID\Tests\CashID;

use BitcoinPHP\BitcoinECDSA\BitcoinECDSA;
use CashID\RequestGenerator;
use CashID\ResponseHandler;
use CashID\Tests\CashID\ResponseGenerator;

class ResponseHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->bitcoinECDSA = new BitcoinECDSA();
        $this->bitcoinECDSA->setPrivateKeyWithWif('L1M8W4jMqqu5h24Nzxf1sy5eHo2aSxdwab8h1fkP5Pt9ATfnxfda');
        $this->cashaddr = 'qpjvm3u8cvjddupctguwatrlaxtutprg8s04ekldyr';
        $this->metadata = [
            'name' => 'Alice',
            'family' => 'Smith',
            'nickname' => 'ajsmith',
            'age' => 20,
            'gender' => 'female',
            'birthdate' => '1999-01-01',
            'national' => 'USA',
            'country' => 'USA',
            'state' => 'CA',
            'city' => 'Los Angeles',
            'streetname' => 'Main',
            'streetnumber' => '123',
            'email' => 'ajsmith@example.com',
            'social' => '123-45-6789',
            'phone' => '123-123-1234',
            'postal' => '12345',
        ];
        $this->generator = new RequestGenerator("demo.cashid.info", "/api/parse.php");
        $this->response_generator = new ResponseGenerator($this->metadata);
        $this->handler = new ResponseHandler("demo.cashid.info", "/api/parse.php");
    }

    /**
     * @testCase testParseRequest
     * @dataProvider dataProviderForTestParseRequest
     */
    public function testParseRequest(string $request, array $expected_array)
    {
        $result = $this->handler->parseRequest($request);
        $this->assertEquals($expected_array, $result);
    }

    public function dataProviderForTestParseRequest()
    {
        return [
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

    /**
     * @testCase testInvalidResponse
     * @runInSeparateProcess
     * @dataProvider dataProviderForInvalidResponse
     */
    public function testInvalidResponse(string $JSON_string, array $response_array)
    {
        $this->assertFalse($this->handler->validateRequest($JSON_string));
        $this->expectOutputString(json_encode($response_array));
        $this->handler->confirmRequest();
    }

    public function dataProviderForInvalidResponse()
    {
        return [
            [  // Not a JSON String
                'Not JSON',
                [
                     "status" => 200,
                     "message" => "Response data is not a valid JSON object.",
                ],
            ],
            [  // Missing request
                '{"address": "qqagsast3fq0g43wnrnweefjsk28pmyvwg7t0jqgg4"}',
                [
                     "status" => 211,
                     "message" => "Response data is missing required 'request' property.",
                ],
            ],
            [  // Missing address
               '{
                    "request": "cashid:bitcoin.com/api/cashid?a=register&d=newsletter&r=i12l1c1&o=i458l3&x=95261230581"
                }',
                [
                     "status" => 212,
                     "message" => "Response data is missing required 'address' property.",
                ],
            ],
            [  // Missing Signature
                '{
                    "request": "cashid:bitcoin.com/api/cashid?a=register&d=newsletter&r=i12l1c1&o=i458l3&x=95261230581",
                    "address": "qqagsast3fq0g43wnrnweefjsk28pmyvwg7t0jqgg4"
                }',
                [
                     "status" => 213,
                     "message" => "Response data is missing required 'signature' property.",
                ],
            ],
            [  // Mismatched domain
                '{
                    "request": "cashid:bitcoin.com/api/cashid?a=register&d=newsletter&r=i12l1c1&o=i458l3&x=95261230581",
                    "address": "qqagsast3fq0g43wnrnweefjsk28pmyvwg7t0jqgg4",
                    "signature": "IKjtNWdIp+tofJQrhxBrq91jLwdmOVNlMhfnKRiaC2t2C7vqsHRoUA+BkdgjnOqX6hv4ZdeG9ZpB6dMh/sXJg/0="
                }',
                [
                     "status" => 131,
                     "message" => "Request domain 'bitcoin.com' is invalid, this service uses 'demo.cashid.info'.",
                ],
            ],
            [  // Incorrect scheme
                '{
                    "request": "cashid1:bitcoin.com/api/cashid?a=register&d=newsletter&r=i12l1c1&o=i458l3&x=95261230581",
                    "address": "qqagsast3fq0g43wnrnweefjsk28pmyvwg7t0jqgg4",
                    "signature": "IKjtNWdIp+tofJQrhxBrq91jLwdmOVNlMhfnKRiaC2t2C7vqsHRoUA+BkdgjnOqX6hv4ZdeG9ZpB6dMh/sXJg/0="
                }',
                [
                     "status" => 121,
                     "message" => "Request scheme 'cashid1:' is invalid, should be 'cashid:'.",
                ],
            ],
            [  // Missing nonce
                '{
                    "request": "cashid:demo.cashid.info/api/cashid?a=register&d=newsletter&r=i12l1c1&o=i458l3",
                    "address": "qqagsast3fq0g43wnrnweefjsk28pmyvwg7t0jqgg4",
                    "signature": "IKjtNWdIp+tofJQrhxBrq91jLwdmOVNlMhfnKRiaC2t2C7vqsHRoUA+BkdgjnOqX6hv4ZdeG9ZpB6dMh/sXJg/0="
                }',
                [
                     "status" => 113,
                     "message" => "Request parameter 'nonce' is missing.",
                ],
            ],
            [  // Request was not issued.
                '{
                    "request": "cashid:demo.cashid.info/api/cashid?a=register&d=newsletter&r=i12l1c1&o=i458l3&x=95261230581",
                    "address": "qqagsast3fq0g43wnrnweefjsk28pmyvwg7t0jqgg4",
                    "signature": "IKjtNWdIp+tofJQrhxBrq91jLwdmOVNlMhfnKRiaC2t2C7vqsHRoUA+BkdgjnOqX6hv4ZdeG9ZpB6dMh/sXJg/0="
                }',
                [
                     "status" => 132,
                     "message" => "The request nonce was not issued by this service.",
                ],
            ],
        ];
    }

    /**
     * @testCase testInvalidSignedResponse
     * @runInSeparateProcess
     * @dataProvider dataProviderForInvalidSignedResponse
     */
    public function testInvalidSignedResponse(array $request, array $response, array $confirmation)
    {
        $json_request = $this->generator->createRequest($request['action'], $request['data'], $request['metadata']);
        $response_array = $this->response_generator->createResponse($json_request);

        // Replace the correct values with values from the dataProvider
        foreach ($response as $key => $value) {
            $response_array[$key] = $value;
        }
        $this->assertFalse($this->handler->validateRequest(json_encode($response_array)));
        $this->expectOutputString(json_encode($confirmation));
        $this->handler->confirmRequest();
    }

    public function dataProviderForInvalidSignedResponse()
    {
        return [
            [ // Missing required field
                [
                    'action' => 'login',
                    'data' => '987',
                    'metadata' => [
                        'optional' => [
                            'position' => ['streetname'],
                        ],
                        'required' => [
                            'identity' => ['nickname'],
                        ],
                    ],
                ],
                [
                    'metadata' => [
                        'streetname' => 'Main',
                    ],
                ],
                [
                    'status' => 214,
                    'message' => "The required metadata field(s) 'nickname' was not provided.",
                ],
            ],
            [ // Extra optional field
                [
                    'action' => 'login',
                    'data' => '987',
                    'metadata' => [
                        'optional' => [
                            'position' => ['streetnumber'],
                        ],
                        'required' => [
                            'identity' => ['nickname'],
                        ],
                    ],
                ],
                [
                    'metadata' => [
                        'streetname' => 'Main',
                        'nickname' => 'ajsmith',
                    ],
                ],
                [
                    'status' => 234,
                    'message' => "The metadata field 'streetname' was not part of the request.",
                ],
            ],
            [ // Empty metadata field
                [
                    'action' => 'login',
                    'data' => '987',
                    'metadata' => [
                        'optional' => [
                            'position' => ['streetnumber'],
                        ],
                        'required' => [
                            'identity' => ['nickname'],
                        ],
                    ],
                ],
                [
                    'metadata' => [
                        'streetnumber' => '',
                        'nickname' => 'ajsmith',
                    ],
                ],
                [
                    'status' => 223,
                    'message' => "The metadata field 'streetnumber' did not contain any value.",
                ],
            ],
            [ // Incorrect Signature
                [
                    'action' => 'login',
                    'data' => '987',
                    'metadata' => [
                        'optional' => [
                            'position' => ['streetnumber'],
                        ],
                        'required' => [
                            'identity' => ['nickname'],
                        ],
                    ],
                ],
                [
                    'signature' => 'IKjtNWdIp+tofJQrhxBrq91jLwdmOVNlMhfnKRiaC2t2C7vqsHRoUA+BkdgjnOqX6hv4ZdeG9ZpB6dMh/sXJg/0=',
                ],
                [
                    'status' => 233,
                    'message' => "Signature verification failed.",
                ],
            ],
        ];
    }

    public function dataProviderForUserInitiatedResponse()
    {
        return [
            [ // Old request
                [
                    'request' => 'cashid:sensitive.cash/api/cashid?a=delete&x=20180929T063418Z',
                    'address' => 'qzvelmkfzvq8gw0d4fvmf904ghefq66keq68qwupsv',
                    'signature' => 'IDwIyQCsmFKwWWibwtxVqppt+KCDBgTKy4IN8+rL+8a9XtGN/AAl/koKPKnIQOr2/nlzOW9XaxtWP96298XkiJE='
                 ],
                 [
                    'status' => 132,
                    'message' => 'Request nonce for user initated action is not a valid and recent timestamp.',
                 ],
            ],
        ]
    }
    /**
     * @testCase ConfirmRequestHeadersSentException
     */
    public function testConfirmRequestHeadersSentException()
    {
        // PHPUnit has already sent headers at this point
        $this->expectException(\Exception::class);
        $this->handler->confirmRequest();
    }
    
    /**
     * @testCase ConfirmRequestNotVerifiedException
     * @runInSeparateProcess
     */
    public function testConfirmRequestNotVerifiedException()
    {
        $this->expectException(\Exception::class);
        $this->handler->confirmRequest();
    }

    /**
     * @testCase ConfirmRequestNotVerifiedException
     * @runInSeparateProcess
     */
    public function testInvalidateRequest()
    {
        $this->handler->invalidateRequest(142, 'test');
        $this->expectOutputString(json_encode(["status" => "142", "message" => "test"]));
        $this->handler->confirmRequest();
    }
}
