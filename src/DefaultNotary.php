<?php

namespace CashID;

use BitcoinPHP\BitcoinECDSA\BitcoinECDSA;
use Submtd\CashaddrConverter\CashaddrConverter;

class DefaultNotary implements NotaryInterface
{
    public function checkSignature(string $address, string $signature, string $message)
    {
            $converter = new CashaddrConverter();
            $legacy_address = $converter->convertFromCashaddr($address);
            $bitcoinECDSA = new BitcoinECDSA();
            return $bitcoinECDSA->checkSignatureForMessage($legacy_address, $signature, $message);
    }

    public function signMessage(string $address, string $message)
    {
        $this->bitcoinECDSA = new BitcoinECDSA();
        $converter = new CashaddrConverter();
        $legacy_address = $converter->convertFromCashaddr($address);
        $this->bitcoinECDSA->setPrivateKeyWithWif($legacy_address);
        return $bitcoinECDSA->signMessage($message, true);
    }
}
