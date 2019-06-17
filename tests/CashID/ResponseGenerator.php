<?php

namespace CashID\Tests\CashID;

use BitcoinPHP\BitcoinECDSA\BitcoinECDSA;

class ResponseGenerator
{
    public function __construct($private_key, $request)
    {
        $this->bitcoinECDSA = new BitcoinECDSA();
        $this->bitcoinECDSA->setPrivateKeyWithWif($private_key);
        $this->request = $request;
    }

    public function createResponse($request_string): string
    {
        $converter = new \Submtd\CashaddrConverter\CashaddrConverter();
        $cashaddr = $converter->convertToCashaddr($this->bitcoinECDSA->getWif());
        
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
