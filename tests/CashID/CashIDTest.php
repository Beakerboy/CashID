<?php
namespace CashID\Tests\CashID;

use CashID\CashID;

class CashIDTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->cashid = new CashID();
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
                    'identification' => ''
                    'name' => ''
                    'family' => ''
                    'nickname' => ''
                    'age' => ''
                    'gender' => ''
                    'birthdate' => ''
                    'picture' => ''
                    'national' => ''
                    'country' => ''
                    'state' => ''
                    'city' => ''
                    'streetname' => '4',
                ],
                "required" => [
                    'contact' => ''
                    'identification' => ''
                    'name' => ''
                    'family' => ''
                    'nickname' => ''
                    'age' => ''
                    'gender' => ''
                    'birthdate' => ''
                    'picture' => ''
                    'national' => ''
                    'position' => ''
                    'country' => ''
                    'state' => ''
                    'city' => ''
                    'streetname' => ''
                    'streetnumber' => ''
                    'residence' => ''
                    'coordinate' => ''
                    'email' => ''
                    'instant' => ''
                    'social' => '3'
                ],
                'nonce' => $nonce,
            ],
            'scheme' => 'cashid:',
            'domain' => 'demo.cashid.info',
            'path' => '/api/parse.php',
        ];
        $this->assertEquals($expected_array, $request_parts);
    }
}
