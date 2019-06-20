<?php

namespace CashID;

/**
 * Notary Interface
 *
 * An interface that defines the expected behavior in signing meassages
 * and validating signatures.
 */
interface NotaryInterface
{
    public function checkSignature(string $address, string $signature, string $message);
    
    public function signMessage(string $address, string $message);
}
