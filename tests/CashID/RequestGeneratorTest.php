<?php
namespace CashID\Tests\CashID;

use CashID\RequestGenerator;

class RequestGeneratorTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->generator = new RequestGenerator("demo.cashid.info", "/api/parse.php");
    }

    /**
     * @testCase constructor
     */
    public function testConstructor()
    {
        $this->assertInstanceOf(RequestGenerator::class, $this->generator);
    }
    
    /**
     * @testCase completeProcess
     * @runInSeparateProcess
     */
    public function testCompleteProcess()
    {
        $requestURI = $this->cashid->createRequest();
        $response = [];
        $response['request'] = $requestURI;
        
        $bitcoinECDSA = new BitcoinECDSA();
        $bitcoinECDSA->setPrivateKeyWithWif('L1M8W4jMqqu5h24Nzxf1sy5eHo2aSxdwab8h1fkP5Pt9ATfnxfda');
        $public_key = 'qpjvm3u8cvjddupctguwatrlaxtutprg8s04ekldyr';
        
        $signature = $bitcoinECDSA->signMessage($requestURI, true);
        
        $response['address'] = $public_key;
        $response['signature'] = $signature;
        $JSON_string = json_encode($response);
        $validation_response = [
            "action" => "auth",
            "data" => "",
            "request" => $requestURI,
            "address" => $public_key,
            "signature" => $signature,
        ];
        $this->assertEquals($validation_response, $this->cashid->validateRequest($JSON_string));
        
        $response_array= [
            "status" => 0,
            "message" => "",
        ];

        $this->expectOutputString(json_encode($response_array));
        $this->cashid->confirmRequest();
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
        $requestURI = $this->generator->createRequest("login", "15366-4133-6141-9638", $metadata);
        $this->assertEquals("cashid:demo.cashid.info/api/parse.php?a=login&d=15366-4133-6141-9638&r=c3&o=p4&x=", substr($requestURI, 0, -9));
        $nonce = substr($requestURI, -9);
        $this->assertRegExp('/^\d{9}$/', $nonce);
    }
}
