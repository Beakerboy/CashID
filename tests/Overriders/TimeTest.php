<?php

namespace CashID;

class TimeTest extends \PHPUnit\Framework\TestCase
{
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
