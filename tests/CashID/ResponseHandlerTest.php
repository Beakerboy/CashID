<?php

namespace CashID\Tests\CashID;

use CashID\RequestCacheInterface;
use CashID\RequestGenerator;
use CashID\ResponseHandler;
use CashID\Tests\ResponseGenerator;

class ResponseHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
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
        $this->responder = new ResponseGenerator($this->metadata);
        $this->handler = new ResponseHandler("demo.cashid.info", "/api/parse.php");
    }

    /**
     * Test the parseRequest() function
     *
     * Ensure the function produces the correct array from the given JSON string.
     *
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
     * Test failures in the validateRequest() function
     *
     * Ensure the function throws the correct exception for malformed requests.
     * All of these failures occur before the function checks if the nonce was actually
     * created by the requestGenerator, so we can pass them to validateRequest() in isolation.
     *
     * @testCase testInvalidResponse
     * @runInSeparateProcess
     * @dataProvider dataProviderForInvalidResponse
     * @dataProvider dataProviderForUserInitiatedResponse
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

    public function dataProviderForUserInitiatedResponse()
    {
        return [
            [ // Old request
                '{
                    "request": "cashid:demo.cashid.info/api/parse.php?a=delete&x=20180929T063418Z",
                    "address": "qzvelmkfzvq8gw0d4fvmf904ghefq66keq68qwupsv",
                    "signature": "IDwIyQCsmFKwWWibwtxVqppt+KCDBgTKy4IN8+rL+8a9XtGN/AAl/koKPKnIQOr2/nlzOW9XaxtWP96298XkiJE="
                 }',
                 [
                    'status' => 132,
                    'message' => 'Request nonce for user initated action is not a valid and recent timestamp.',
                 ],
            ],
        ];
    }

    /**
     * Test failures in the validateRequest() function.
     *
     * Ensure the function throws the correct exception for malformed requests.
     * All these failures are checked after the signature is checked.
     *
     * @testCase testInvalidSignedResponse
     * @runInSeparateProcess
     * @dataProvider dataProviderForInvalidSignedResponse
     */
    public function testInvalidSignedResponse(array $request, array $response, array $confirmation)
    {
        // Create the request from the provided $request array
        $json_request = $this->generator->createRequest($request['action'], $request['data'], $request['metadata']);

        // Create a valid response given the request and the default metadata
        $response_array = $this->responder->createResponse($json_request);

        // Replace the correct values with values from the dataProvider
        foreach ($response as $key => $value) {
            $response_array[$key] = $value;
        }

        // Verify that the validation fails
        $this->assertFalse($this->handler->validateRequest(json_encode($response_array)));

        // Verify that the correct exception and message is produced
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
                            'identification' => ['nickname'],
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
                            'identification' => ['nickname'],
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
                            'identification' => ['nickname'],
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
                            'identification' => ['nickname'],
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

    /**
     * Test failures in the validateRequest() function.
     *
     * Ensure that the function correctly identifies tampered requests
     *
     * @testCase testTamperedRequest
     * @runInSeparateProcess
     * @dataProvider dataProviderForTamperedRequest
     */
    public function testTamperedRequest(array $original_request, string $new_request, array $confirmation)
    {
        // Create the request from the provided $request array
        $json_request = $this->generator->createRequest($original_request['action'], $original_request['data'], $original_request['metadata']);

        // Alter the request
        // Append the nonce from the original request to the new request
        $new_json_request = $new_request . substr($json_request, -9);
        
        // Create a valid response given the altered request and the default metadata
        $response_array = $this->responder->createResponse($new_json_request);

        // Verify that the validation fails
        $this->assertFalse($this->handler->validateRequest(json_encode($response_array)));

        // Verify that the correct exception and message is produced
        $this->expectOutputString(json_encode($confirmation));
        $this->handler->confirmRequest();
    }

    public function dataProviderForTamperedRequest()
    {
        return [
            [
                [
                    'action' => 'login',
                    'data' => '987',
                    'metadata' => [
                        'optional' => [
                            'position' => ['streetnumber'],
                        ],
                        'required' => [
                            'identification' => ['nickname'],
                        ],
                    ],
                ],
                "cashid:demo.cashid.info/api/parse.php?a=login&d=986&r=c3&o=p4&x=",
                [
                    'status' => 141,
                    'message' => "The response does not match the request parameters.",
                ],
                
            ],
        ];
    }

    /**
     * Test that a response to an old request causes a failure
     *
     * @runInSeparateProcess
     */
    public function testOldRequest()
    {
        \CashID\TimeOverrider::setValues([strtotime('-1 month')]);
        \CashID\TimeOverrider::setOverride();
        $json_request = $this->generator->createRequest();
        \CashID\TimeOverrider::unsetOverride();

        // Create the response
        $response_array = $this->responder->createResponse($json_request);
        
        // Validate against today's date and verify failure
        $this->assertFalse($this->handler->validateRequest(json_encode($response_array)));

        // Verify that the correct exception and message is produced
        $this->expectOutputString('{"status":142,"message":"The request has expired and is no longer available."}');
        $this->handler->confirmRequest();
    }

    /**
     * Test APCu response storage failure
     *
     * @runInSeparateProcessP
     */
    public function testAPCuResponseFailure()
    {
        $cache = $this->createMock(RequestCacheInterface::class);
        $cache->method('store')->willReturn(false);
        $cache->method('fetch')->will($this->returnCallback(
            function ($key) {
                return apcu_fetch($key);
            }
        ));
        $notary = new \CashID\DefaultNotary();
        $handler = new ResponseHandler("demo.cashid.info", "/api/parse.php", $notary, $cache);
        $json_request = $this->generator->createRequest();

        // Create the response
        $response_array = $this->responder->createResponse($json_request);

        // Validate storage failure
        $this->assertFalse($handler->validateRequest(json_encode($response_array)));

        // Verify that the correct exception and message is produced
        $this->expectOutputString('{"status":331,"message":"Internal server error, could not store response object."}');
        $this->handler->confirmRequest();
    }

    /**
     * Test APCu response storage failure
     * @runInSeparateProcess
     *
     */
    public function testAPCuConfirmationFailure()
    {
        $cache = $this->createMock(RequestCacheInterface::class);
        $cache->method('store')->will($this->onConsecutiveCalls(true, false));
        $notary = new \CashID\DefaultNotary();
        $handler = new ResponseHandler("demo.cashid.info", "/api/parse.php", $notary, $cache);
        $json_request = $this->generator->createRequest();

        // Create the response
        $response_array = $this->responder->createResponse($json_request);

        // Validate storage failure
        $this->assertFalse($handler->validateRequest(json_encode($response_array)));

        // Verify that the correct exception and message is produced
        $this->expectOutputString('{"status":331,"message":"Internal server error, could not store confirmation object."}');
        $this->handler->confirmRequest();
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
