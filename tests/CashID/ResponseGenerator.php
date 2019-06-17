<?php

namespace CashID\Tests\CashID;

use BitcoinPHP\BitcoinECDSA\BitcoinECDSA;

class ResponseGenerator
{
    public function __construct($request, $metadata)
    {
        $this->bitcoinECDSA = new BitcoinECDSA();
        $this->bitcoinECDSA->setPrivateKeyWithWif('L1M8W4jMqqu5h24Nzxf1sy5eHo2aSxdwab8h1fkP5Pt9ATfnxfda');
        $this->cashaddr = 'qpjvm3u8cvjddupctguwatrlaxtutprg8s04ekldyr';
        $this->request = $request;
        $this->metadata = $metadata;
    }

    public function createResponse($request_string): string
    {
        $converter = new \Submtd\CashaddrConverter\CashaddrConverter();
        
        return [
            "request" => $this->request,
            "address" => $cashaddr,
            "signature" => $this->signMessage($this->request),
        ];
    }
    
    private function signMessage(string $message): string
    {
        return $this->bitcoinECDSA->signMessage($message, true);
    }
}
