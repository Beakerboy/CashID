<?php

namespace CashID;

class ResponseHandlerTest extends \PHPUnit\Framework\TestCase
{
    use \phpmock\phpunit\PHPMock;

    /**
     * Test that a response to an old request causes a failure
     *
     * @runInSeparateProcess
     */
    public function testOldRequest()
    {
        $time = $this->getFunctionMock("CashID", "time");
        $time->expects($this->exactly(2))->will($this->onConsecutiveCalls(strtotime('-1 month'), strtotime('now')));
        $json_request = $this->generator->createRequest();

        // Create the response
        $response_array = $this->responder->createResponse($json_request);
        
        // Validate against today's date and verify failure
        $this->assertFalse($this->handler->validateRequest(json_encode($response_array)));
        // Verify that the correct exception and message is produced
        $this->expectOutputString('{"status":142,"message":"The request has expired and is no longer available."}');
        $this->handler->confirmRequest();
    }
}
