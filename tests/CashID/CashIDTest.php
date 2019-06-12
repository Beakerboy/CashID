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
        $this->assertRegExp("/^\d{9}$/", substr($requestURI, -9));
        $request_parts = cashid->parseRequest($requestURI);

        $expected_array = [
            "parameters" => [
                "action" => "login",
                "data" => "15366-4133-6141-9638",
                "optional" => ["position" => ["streetname"]],
                "required" => ["contact" => ["social"]],
        ];
        $this->assertEquals($expected_array, $request_parts);
    }
}
