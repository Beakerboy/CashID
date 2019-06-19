<?php

namespace CashID\Tests;

use BitcoinPHP\BitcoinECDSA\BitcoinECDSA;
use CashID\API;

class ResponseGenerator
{
    /**
     * Create a new response generator
     *
     * @param array $metadata
     *  all the metadata available to choose from
     */
    public function __construct($metadata)
    {
        $this->bitcoinECDSA = new BitcoinECDSA();
        $this->bitcoinECDSA->setPrivateKeyWithWif('L1M8W4jMqqu5h24Nzxf1sy5eHo2aSxdwab8h1fkP5Pt9ATfnxfda');
        $this->cashaddr = 'qpjvm3u8cvjddupctguwatrlaxtutprg8s04ekldyr';
        $this->metadata = $metadata;
    }

    /**
     * Create a response
     *
     * An array is returned that includes all the required information
     * in the CashID request. The $include_optional parameter determines
     * if all or none of the optional fields are returned.
     *
     * todo: handle cases where the required field in not in the object’s
     *  metadata collection.
     *
     * @param array $request
     * @param boolean $include_optional
     * @returns array
     */
    public function createResponse($request_string, $include_optional = true): array
    {
        // Parse the request string to see what metadata is needed
        // First pull out all parameters
        $response_array = \CashID\ResponseHandler::parseRequest($request_string);
        
        $meta_keys = $response_array['parameters']['required'];
        
        // Merge together the optional and required parameters
        if ($include_optional) {
            $meta_keys = array_merge($response_array['parameters']['optional'], $meta_keys);
        }
        
        // Initialize the array
        $return_meta = [];
        
        // Loop through the optional and required values and
        // save the requested fields.
        foreach ($meta_keys as $key => $value) {
            $return_meta[$key] = $this->metadata[$key];
        }
        return [
            'request' => $request_string,
            'address' => $this->cashaddr,
            'signature' => $this->signMessage($request_string),
            'metadata' => $return_meta,
        ];
    }

    public function createJSONResponse($request_string, $include_optional = true)
    {
        return json_encode(createResponse($request_string, $include_optional = true));
    }

    /**
     * Sign a message
     *
     * Use the object’s private key to sign the given message.
     *
     * @param string $message
     * @param string
     *  The signature string
     */
    private function signMessage(string $message): string
    {
        return $this->bitcoinECDSA->signMessage($message, true);
    }
}
