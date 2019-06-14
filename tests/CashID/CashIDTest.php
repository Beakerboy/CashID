<?php
namespace CashID\Tests\CashID;

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
        
        // Not a JSON string
        $this->assertFalse($this->cashid->validateRequest("Not JSON"));
        
        // No request field
        $this->assertFalse($this->cashid->validateRequest("{'address': 'qqagsast3fq0g43wnrnweefjsk28pmyvwg7t0jqgg4'}"));
        
        // No address Field
        $JSON = "{
            'request': 'cashid:bitcoin.com/api/cashid?a=register&d=newsletter&r=i12l1c1&o=i458l3&x=95261230581'
        }";
        $this->assertFalse($this->cashid->validateRequest($JSON));
        
        // No signature Field
        $JSON = "{
            'request': 'cashid:bitcoin.com/api/cashid?a=register&d=newsletter&r=i12l1c1&o=i458l3&x=95261230581',
            'address': 'qqagsast3fq0g43wnrnweefjsk28pmyvwg7t0jqgg4'
        }";
        $this->assertFalse($this->cashid->validateRequest($JSON));
        
        // Incorrect domain
        $JSON = "{
            'request': 'cashid:bitcoin.com/api/cashid?a=register&d=newsletter&r=i12l1c1&o=i458l3&x=95261230581',
            'address': 'qqagsast3fq0g43wnrnweefjsk28pmyvwg7t0jqgg4',
            'signature': 'IKjtNWdIp+tofJQrhxBrq91jLwdmOVNlMhfnKRiaC2t2C7vqsHRoUA+BkdgjnOqX6hv4ZdeG9ZpB6dMh/sXJg/0='
        }";
        $this->assertFalse($this->cashid->validateRequest($JSON));
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
     * @dataProvider dataProviderForInvalidRequest
     */
    public function testInvalidRequest($JSON_string)
    {
        $this->assertFalse($this->cashid->validateRequest($JSON));
    }

    public function dataProviderForInvalidRequest()
    {
        return [
            [  // Not a JSON String
                "Not JSON",
            ],
            [  // Missing request
                "{'address': 'qqagsast3fq0g43wnrnweefjsk28pmyvwg7t0jqgg4'}",
            ],
            [  // Missing address
               "{
                    'request': 'cashid:bitcoin.com/api/cashid?a=register&d=newsletter&r=i12l1c1&o=i458l3&x=95261230581'
                }"
            ],
            [  // Missing Signature
                "{
                    'request': 'cashid:bitcoin.com/api/cashid?a=register&d=newsletter&r=i12l1c1&o=i458l3&x=95261230581',
                    'address': 'qqagsast3fq0g43wnrnweefjsk28pmyvwg7t0jqgg4'
                }",
            ],
            [  // Mismatched domain
                "{
                    'request': 'cashid:bitcoin.com/api/cashid?a=register&d=newsletter&r=i12l1c1&o=i458l3&x=95261230581',
                    'address': 'qqagsast3fq0g43wnrnweefjsk28pmyvwg7t0jqgg4',
                    'signature': 'IKjtNWdIp+tofJQrhxBrq91jLwdmOVNlMhfnKRiaC2t2C7vqsHRoUA+BkdgjnOqX6hv4ZdeG9ZpB6dMh/sXJg/0='
                }"
            ],
        ];
    }
}
