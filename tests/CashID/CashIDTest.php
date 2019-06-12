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
        $requestURI = $this->cashid->createRequest();
        $this->assertNotFalse($requestURI);
    }
}
