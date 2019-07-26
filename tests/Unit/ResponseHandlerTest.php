<?php

namespace CashID\Tests\CashID;

use CashID\Cache\RequestCacheInterface;
use CashID\RequestGenerator;
use CashID\ResponseHandler;
use CashID\Tests\ResponseGenerator;

/**
 * Test the ResponseHandler class
 *
 * Unit tests for each function
 */
class ResponseHandlerTest extends \PHPUnit\Framework\TestCase
{
    use \phpmock\phpunit\PHPMock;

    private $generator;
    private $responder;
    private $handler;
    private $cashaddr = 'qpjvm3u8cvjddupctguwatrlaxtutprg8s04ekldyr';
    private $metadata = [
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

    /**
     * Set up the default objects for the test
     */
    public function setUp()
    {
        $this->generator = new RequestGenerator("demo.cashid.info", "/api/parse.php");
        $this->responder = new ResponseGenerator($this->metadata);
        $this->handler = new ResponseHandler("demo.cashid.info", "/api/parse.php");
    }

    /**
     * Test failures in the validateRequest() function
     *
     * Ensure the function throws the correct exception for malformed requests.
     * All of these failures occur before the function checks if the nonce was
     * actually created by the requestGenerator, so we can pass them to
     * validateRequest() in isolation. This should probably be changed in case
     * the function is refactored.
     *
     * @runInSeparateProcess
     * @dataProvider dataProviderForInvalidResponse
     * @dataProvider dataProviderForUserInitiatedResponse
     */
    public function testInvalidResponse(string $JSON_string, array $response_array)
    {
        // Verify that the function return false
        $this->assertFalse($this->handler->validateRequest($JSON_string));

        // Verify the correct status code is produce
        $this->expectOutputString(json_encode($response_array));
        $this->handler->confirmRequest();
    }

    /**
     * Provide an assortment of malformed response strings
     */
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
     * Provide an assortment of malformed user-initiated response strings
     */
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
     * Test APCu response storage failure
     *
     * @runInSeparateProcess
     */
    public function testAPCuResponseFailure()
    {
        // Create a mock request cache whos storage fails, but successfully fetches
        $cache = $this->createMock(RequestCacheInterface::class);
        $cache->method('store')->willReturn(false);
        $cache->method('fetch')->will($this->returnCallback(
            function ($key) {
                return apcu_fetch($key);
            }
        ));

        // Use the default notary
        $notary = new \CashID\Notary\DefaultNotary();

        // Create or hobbled response handler
        $handler = new ResponseHandler("demo.cashid.info", "/api/parse.php", $notary, $cache);

        // Generate a request using the fully functional generator
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
     *
     * @runInSeparateProcess
     */
    public function testAPCuConfirmationFailure()
    {
        // Create a mock request cache whos storage fails the second time,
        // but successfully fetches
        $cache = $this->createMock(RequestCacheInterface::class);
        $cache->method('store')->will($this->onConsecutiveCalls(true, false));
        $cache->method('fetch')->will($this->returnCallback(
            function ($key) {
                return apcu_fetch($key);
            }
        ));

        // Use the default notary
        $notary = new \CashID\Notary\DefaultNotary();

        // Create a hobbled handler
        $handler = new ResponseHandler("demo.cashid.info", "/api/parse.php", $notary, $cache);

        // Generate a request using the fully functional generator
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
     * Test APCu response storage failure
     *
     * @runInSeparateProcess
     */
    public function testAPCuAlterFailure()
    {
        // Create a mock request cache whos storage fails the second time,
        // but successfully fetches
        $cache = $this->createMock(RequestCacheInterface::class);
        $cache->method('store')->will($this->onConsecutiveCalls(true, true, false));
        $cache->method('fetch')->will($this->returnCallback(
            function ($key) {
                return apcu_fetch($key);
            }
        ));
        $cache->method('delete')->will($this->returnCallback(
            function ($key) {
                return apcu_delete($key);
            }
        ));

        // Use the default notary
        $notary = new \CashID\Notary\DefaultNotary();

        // Create a hobbled handler
        $handler = new ResponseHandler("demo.cashid.info", "/api/parse.php", $notary, $cache);

        // Generate a request using the fully functional generator
        $json_request = $this->generator->createRequest();

        // Create the response
        $response = $this->responder->createJSONResponse($json_request);

        // Validate storage failure
        $this->assertFalse($handler->validateRequest($response));

        // Verify that the correct exception and message is produced
        $this->expectOutputString('{"status":331,"message":"Internal server error, could not alter request object."}');
        $this->handler->confirmRequest();
    }
    
    /**
     * Expect an exception if headers have been sent prior to confirmation
     *
     */
    public function testConfirmRequestHeadersSentException()
    {
        // PHPUnit has already sent headers at this point
        $this->expectException(\Exception::class);
        $this->handler->confirmRequest();
    }
    
    /**
     * Expect an exception if no request was every sent
     *
     * This runs in a separate process to ensure the exception is independant
     * from the exception thrown in ConfirmRequestHeadersSentException
     *
     * @runInSeparateProcess
     */
    public function testConfirmRequestNotVerifiedException()
    {
        $this->expectException(\Exception::class);
        $this->handler->confirmRequest();
    }

    /**
     * Test that the invalidateRequest function returns the expected output
     *
     * @runInSeparateProcess
     */
    public function testInvalidateRequest()
    {
        $this->handler->invalidateRequest(142, 'test');
        $this->expectOutputString(json_encode(["status" => "142", "message" => "test"]));
        $this->handler->confirmRequest();
    }

    /**
     * Test that a response to an old request causes a failure
     *
     * This test runs in a seperate process because earlier calls to time()
     * will prevent it from being overridden
     *
     * @runInSeparateProcess
     */
    public function testOldRequest()
    {
        $time = $this->getFunctionMock('CashID', "time");
        $time->expects($this->exactly(4))->willReturn(strtotime('-1 month'), strtotime('now'), strtotime('now'), strtotime('now'));

        // Create a request
        $json_request = $this->generator->createRequest();

        // Create the response
        $response_array = $this->responder->createResponse($json_request);
        
        // Validate against today's date and verify failure
        $this->assertFalse($this->handler->validateRequest(json_encode($response_array)));

        // Verify that the correct exception and message is produced
        $this->expectOutputString('{"status":142,"message":"The request has expired and is no longer available."}');
        $this->handler->confirmRequest();
    }

    /**
     * Test that a consumed request cannot be reused
     *
     * @runInSeparateProcess
     */
    public function testConsumedRequest()
    {
        // Create a request
        $json_request = $this->generator->createRequest();

        // Create the response
        $response = $this->responder->createJSONResponse($json_request);
        
        // Validate
        $this->handler->validateRequest($response);

        // Validating a second time will fail
        $this->assertFalse($this->handler->validateRequest($response));

        // Verify that the correct exception and message is produced
        $this->expectOutputString('{"status":143,"message":"The request has been used and is no longer available."}');
        $this->handler->confirmRequest();
    }
}
