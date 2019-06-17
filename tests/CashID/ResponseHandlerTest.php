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
            'identity' => [
                'name' => 'Alice',
                'family' => 'Smith',
                'nickname' => 'ajsmith',
                'age' => 20,
                'gender' => 'female',
                'birthdate' => '1999-01-01',
                'national' => 'USA',
            ],
            'position' => [
                'country' => 'USA',
                'state' => 'CA',
                'city' => 'Los Angeles',
                'streetname' => 'Main',
                'streetnumber' => '123',
            ],
            'contact' => [
                'email' => 'ajsmith@example.com',
                'social' => '123-45-6789',
                'phone' => '123-123-1234',
                'postal' => '12345',
            ],
        ];
        $this->generator = new RequestGenerator("demo.cashid.info", "/api/parse.php");
        $this->response_generator = new ResponseGenerator($this->metadata);
        $this->handler = new ResponseHandler("demo.cashid.info", "/api/parse.php");
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
    public function testInvalidSignedResponse(array $request, array $response, array $confimation)
    {
        $json_request = $this->generator->createRequest($request['action'], $request['data'], $request['metadata']);
        $json_response = $this->response_generator->createResponse($json_request);
        $response_array = json_decode($json_response);

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
        ];
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
