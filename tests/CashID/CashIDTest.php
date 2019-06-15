<?php
namespace CashID\Tests\CashID;

use BitcoinPHP\BitcoinECDSA\BitcoinECDSA;
use CashID\CashID;

class CashIDTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->cashid = new CashID("demo.cashid.info", "/api/parse.php");
    }

    /**
     * @testCase constructor
     */
    public function testConstructor()
    {
        $this->assertInstanceOf(CashID::class, $this->cashid);
    }
    
    /**
     * @testCase completeProcess
     */
    public function testCompleteProcess()
    {
        $requestURI = $this->cashid->createRequest();
        $response = [];
        $response['request'] = $requestURI;
        
        $bitcoinECDSA = new BitcoinECDSA();
        $bitcoinECDSA->setPrivateKeyWithWif('L1M8W4jMqqu5h24Nzxf1sy5eHo2aSxdwab8h1fkP5Pt9ATfnxfda');
        $public_key = 'qpjvm3u8cvjddupctguwatrlaxtutprg8s04ekldyr';
        
        $signature = $bitcoinECDSA->signMessage($requestURI, true);
        
        $response['address'] = $public_key;
        $response['signature'] = $signature;
        $JSON_string = json_encode($response);
        $this->assertEquals(['action' => 'auth', 'data' => ''], $this->cashid->validateRequest($JSON_string));
        
        $response_array = $response;
        $response_array= [
            "status" => 0,
            "message" => "",
            "request" => $requestURI,
            "address" => $public_key,
            "signature" => $signature,
        ];

        $this->expectOutputString(json_encode($response_array));
        $this->cashid->confirmRequest();
    }
    
    /**
     * @testCase Create Request
     */
    public function testCreateRequest()
    {
        $metadata = [
            "optional" => [
                "position"=> ["streetname"],
            ],
            "required" => [
                "contact"=> ["social"],
            ],
        ];
        $requestURI = $this->cashid->createRequest("login", "15366-4133-6141-9638", $metadata);
        $this->assertEquals("cashid:demo.cashid.info/api/parse.php?a=login&d=15366-4133-6141-9638&r=c3&o=p4&x=", substr($requestURI, 0, -9));
        $nonce = substr($requestURI, -9);
        $this->assertRegExp('/^\d{9}$/', $nonce);
        $request_parts = $this->cashid->parseRequest($requestURI);

        $expected_array = [
            "parameters" => [
                "action" => "login",
                "data" => "15366-4133-6141-9638",
                "optional" => [
                    "position" => "",
                    'identification' => '',
                    'name' => '',
                    'family' => '',
                    'nickname' => '',
                    'age' => '',
                    'gender' => '',
                    'birthdate' => '',
                    'picture' => '',
                    'national' => '',
                    'country' => '',
                    'state' => '',
                    'city' => '',
                    'streetname' => '4',
                ],
                "required" => [
                    'contact' => '',
                    'identification' => '',
                    'name' => '',
                    'family' => '',
                    'nickname' => '',
                    'age' => '',
                    'gender' => '',
                    'birthdate' => '',
                    'picture' => '',
                    'national' => '',
                    'position' => '',
                    'country' => '',
                    'state' => '',
                    'city' => '',
                    'streetname' => '',
                    'streetnumber' => '',
                    'residence' => '',
                    'coordinate' => '',
                    'email' => '',
                    'instant' => '',
                    'social' => '3'
                ],
                'nonce' => $nonce,
            ],
            'scheme' => 'cashid:',
            'domain' => 'demo.cashid.info',
            'path' => '/api/parse.php',
        ];
        $this->assertEquals($expected_array, $request_parts);
        $this->cashid->invalidateRequest(142, 'test');
    }
    
    /**
     * @testCase ConfirmRequestHeadersSentException
     */
    public function testConfirmRequestHeadersSentException()
    {
        // PHPUnit has already sent headers at this point
        $this->expectException(\Exception::class);
        $this->cashid->confirmRequest();
    }
    
    /**
     * @testCase ConfirmRequestNotVerifiedException
     * @runInSeparateProcess
     */
    public function testConfirmRequestNotVerifiedException()
    {
        $this->expectException(\Exception::class);
        $this->cashid->confirmRequest();
    }
    
    /**
     * @testCase testInvalidRequest
     * @runInSeparateProcess
     * @dataProvider dataProviderForInvalidRequest
     */
    public function testInvalidRequest(string $JSON_string, array $response_array)
    {
        $this->assertFalse($this->cashid->validateRequest($JSON_string));
        $this->expectOutputString(json_encode($response_array));
        $this->cashid->confirmRequest();
    }

    public function dataProviderForInvalidRequest()
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
                     "message" => "Request scheme is invalid, should be 'cashid:'.",
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
        ];
    }
}
