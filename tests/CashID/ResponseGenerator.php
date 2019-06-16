<?php

namespace CashID\Tests\CashID;

use BitcoinPHP\BitcoinECDSA\BitcoinECDSA;

class ResponseGenerator
{
    public function __construct($private_key, $metadata)
    {
        $this->bitcoinECDSA = new BitcoinECDSA();
        $this->bitcoinECDSA->setPrivateKeyWithWif($private_key);
        $this->metadata = $metadata;
    }

    public function createResponse($request_string): string
    {
    }
    
    private function signMessage(string $message): string
    {
        return $this->bitcoinECDSA->signMessage($message, true);
    }
}
