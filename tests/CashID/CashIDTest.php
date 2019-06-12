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
            "name" => "Jon",
            "optional" => [
                "address" => "123 Anywhere",
            ],
            "required" => [
                "country" => "Antarctica",
            ],
        ];
        $requestURI = $this->cashid->createRequest("login", "15366-4133-6141-9638", $metadata);
        $this->assertNotFalse($requestURI);
    }
}
