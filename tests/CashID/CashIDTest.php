<?php
namespace CashID\Tests\CashID;

use CashID\RequestGenerator;
use CashID\ResponseHandler;
use CashID\Tests\ResponseGenerator;

class CashIDTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
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
        $this->handler = new ResponseHandler("demo.cashid.info", "/api/parse.php");
        $this->responder = new ResponseGenerator($this->metadata);
    }
    
    /**
     * @testCase completeProcess
     * @runInSeparateProcess
     */
    public function testCompleteProcess()
    {
        $requestURI = $this->generator->createRequest();
        $response_array = $this->responder->createResponse($requestURI);
        $json_response = json_encode($response_array);
        $validation_response = [
            "action" => "auth",
            "data" => "",
            "request" => $requestURI,
            "address" => 'qpjvm3u8cvjddupctguwatrlaxtutprg8s04ekldyr',
            "signature" => $response_array['signature'],
        ];
        $this->assertEquals($validation_response, $this->handler->validateRequest($json_response));
        
        $response_array= [
            "status" => 0,
            "message" => "",
        ];

        $this->expectOutputString(json_encode($response_array));
        $this->handler->confirmRequest();
    }
}
