<?php

namespace CashID\Tests;

use CashID\API;
use CashID\Notary\DefaultNotary;

/**
 * Response Generator
 *
 * This class simulates the actions of a user. The class produces a signed
 * response for a provided request given a collection of metadata.
 */
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
        $this->private_key = 'L1M8W4jMqqu5h24Nzxf1sy5eHo2aSxdwab8h1fkP5Pt9ATfnxfda';
        $this->cashaddr = 'qpjvm3u8cvjddupctguwatrlaxtutprg8s04ekldyr';
        $this->metadata = $metadata;
    }

    /**
     * Create a response
     *
     * An array is returned that includes all the required information in the
     * CashID request. The $include_optional parameter determines if all or
     * none of the optional fields are returned.
     *
     * todo: handle cases where the required field in not in the object’s
     *  metadata collection.
     *
     * @param array $request
     * @param boolean $include_optional
     * @return array
     */
    public function createResponse($request_string, $include_optional = true): array
    {
        // Parse the request string to see what metadata is needed
        // First pull out all parameters
        $response_array = API::parseRequest($request_string);

        $meta_keys = $response_array['parameters']['required'];

        // Merge together the optional and required parameters
        if ($include_optional) {
            $meta_keys = array_merge($response_array['parameters']['optional'], $meta_keys);
        }

        // Initialize the array
        $return_meta = [];
 
        // Loop through the optional and required values and save the requested
        // fields.
        foreach ($meta_keys as $key => $value) {
            $return_meta[$key] = $this->metadata[$key];
        }
        $return_array = [
            'request' => $request_string,
            'address' => $this->cashaddr,
            'signature' => $this->signMessage($request_string),
        ];

        if (count($return_meta) > 0) {
            $return_array['metadata'] = $return_meta;
        }

        return $return_array;
    }

    /**
     * Create a JSON Response
     *
     * The response array is returned as a JSON encoded string.
     * @param array $request
     * @param boolean $include_optional
     * @return string
     */
    public function createJSONResponse($request_string, $include_optional = true): string
    {
        return json_encode($this->createResponse($request_string, $include_optional));
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
        $notary = new DefaultNotary();
        return $notary->signMessage($this->private_key, $message);
    }
}
