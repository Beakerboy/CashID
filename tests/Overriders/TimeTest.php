<?php

namespace CashID;

use CashID\Tests\ResponseGenerator;

class TimeTest extends \PHPUnit\Framework\TestCase
{
    public function setup()
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
     * Test that a response to an old request causes a failure
     *
     * @runInSeparateProcess
     */
    public function testOldRequest()
    {
        \CoreOverrider\OverriderBase::createMock("CashID", "time");
        TimeOverrider::setValues([strtotime('-1 month')]);
        TimeOverrider::setOverride();
        $json_request = $this->generator->createRequest();
        TimeOverrider::unsetOverride();

        // Create the response
        $response_array = $this->responder->createResponse($json_request);
        
        // Validate against today's date and verify failure
        $this->assertFalse($this->handler->validateRequest(json_encode($response_array)));

        // Verify that the correct exception and message is produced
        $this->expectOutputString('{"status":142,"message":"The request has expired and is no longer available."}');
        $this->handler->confirmRequest();
    }
}
