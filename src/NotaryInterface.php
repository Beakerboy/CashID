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
    public function checkSignature($address, $siganture, $message);
    
    public function signMessage($address, $message);
}
