<?php

namespace CashID\Tests\CashID;

use CashID\Services\RequestGenerator;
use CashID\Services\ResponseHandler;
use CashID\Tests\ResponseGenerator;
use Paillechat\ApcuSimpleCache\ApcuCache;

/**
 * CashID Test
 *
 * A complete functional test of a working CashID interaction
 */
class CashIDTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Setup
     *
     * Set the global objects for this test
     */
    public function setUp()
    {
        $cache = new ApcuCache();
        // Define some metatdata for our "user" to respond with
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

        // Create the generator
        $this->generator = new RequestGenerator("demo.cashid.info", "/api/parse.php", $cache);

        // Create a response handler with matching server and script
        $this->handler = new ResponseHandler("demo.cashid.info", "/api/parse.php", $cache);

        // Supply the "user" with their metadata
        $this->responder = new ResponseGenerator($this->metadata);
    }

    /**
     * Test the complete process
     *
     * This test must run in a separate process to prevent header interference
     *
     * @runInSeparateProcess
     */
    public function testCompleteProcess()
    {
        // Create a minimal request
        $requestURI = $this->generator->createRequest();

        // The user creates their response.
        $response_array = $this->responder->createResponse($requestURI);

        // JSON encode the response
        $json_response = json_encode($response_array);

        // The expected validation response
        $validation_response = [
            "action" => "auth",
            "data" => "",
            "request" => $requestURI,
            "address" => 'qpjvm3u8cvjddupctguwatrlaxtutprg8s04ekldyr',
            "signature" => $response_array['signature'],
        ];

        // Assert the validation responds appropriately.
        $this->assertEquals($validation_response, $this->handler->validateRequest($json_response));

        // The expected confirmation
        $confirmation_array = [
            "status" => 0,
            "message" => "",
        ];

        // Assert that the handler responds appropriately
        $this->expectOutputString(json_encode($confirmation_array));
        $this->handler->confirmRequest();
    }
}
