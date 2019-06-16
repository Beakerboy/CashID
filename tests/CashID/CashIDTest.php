<?php
namespace CashID\Tests\CashID;

use BitcoinPHP\BitcoinECDSA\BitcoinECDSA;
use CashID\RequestGenerator;
use CashID\ReaponseHandler;

class CashIDTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->generator = new RequestGenerator("demo.cashid.info", "/api/parse.php");
        $this->handler = new ResponseHandler("demo.cashid.info", "/api/parse.php");
    }
    
    /**
     * @testCase completeProcess
     * @runInSeparateProcess
     */
    public function testCompleteProcess()
    {
        $requestURI = $this->generator->createRequest();
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
        $this->assertEquals($validation_response, $this->handler->validateRequest($JSON_string));
        
        $response_array= [
            "status" => 0,
            "message" => "",
        ];

        $this->expectOutputString(json_encode($response_array));
        $this->handler->confirmRequest();
    }
}
