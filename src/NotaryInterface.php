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
    /**
     * Check Signature
     *
     * @param string $address
     *   The public key of the signing party
     * @param string $signature
     *   The signature
     * @param string $message
     *   The message which was signed
     * @return bool
     *   true if successful
     */
    public function checkSignature(string $address, string $signature, string $message);

    /**
     * Sign Message
     *
     * @param string $address
     *   The private key of the party signing the message
     * @param string $message
     *   The message to be signed
     * @return string
     *   The signature
     */
    public function signMessage(string $address, string $message);
}
