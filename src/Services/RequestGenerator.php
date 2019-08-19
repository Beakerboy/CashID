<?php

namespace CashID\Services;

use CashID\API;
use CashID\Cache\APCuCache;
use CashID\Exceptions\InternalException;

/**
 * Request Generator
 *
 * The RequestGenerator creates CashID requests following the CashID standard.
 * The constructor is given the domain name and path that will be handling the
 * response, while the createRequest function creates a request string from
 * the required data and metadata.
 */
class RequestGenerator extends CashIDService
{
    /**
     * Create a request
     *
     * Given an action, data, and a set of metadata, construct a valid CashID
     * request. A unique random nonce is generated for each request and saved
     * in the user cache.
     *
     * @param string $action
     *  Name of the action the user authenticates to perform
     * @param string $data
     *  Data relevant to the requested action
     * @param array $metadata
     *  Array with requested and optional metadata
     * @return mixed
     *  returns the request URI or false if error
     */
    public function createRequest(?string $action = "", ?string $data = "", ?array $metadata = [])
    {
        try {
            $user_generated = false;
            $nonce = null;
            // Check is the action is a user-initiated action.
            // If so, we will provide a nonce-less request as
            // a favor.
            if (in_array($action, API::USER_ACTIONS)) {
                $user_generated = true;
            } else {
                // generate a random nonce.
                $nonce = rand(100000000, 999999999);

                // Check if the nonce is already used, and regenerate until it does not exist.
                while ($this->cache->has("cashid_request_{$nonce}")) {
                    // generate a random nonce.
                    $nonce = rand(100000000, 999999999);
                }
            }

            // Initialize an empty parameter list.
            $parameters = [];

            // If a specific action was requested, add it to the parameter list.
            if ($action) {
                $parameters['a'] = "a={$action}";
            }

            // If specific data was requested, add it to the parameter list.
            if ($data) {
                $parameters['d'] = "d={$data}";
            }

            // If required metadata was requested, add them to the parameter list.
            if (isset($metadata['required'])) {
                $parameters['r'] = "r=" . $this->encodeRequestMetadata($metadata['required']);
            }

            // If optional metadata was requested, add them to the parameter list.
            if (isset($metadata['optional'])) {
                $parameters['o'] = "o=" . $this->encodeRequestMetadata($metadata['optional']);
            }

            if (!$user_generated) {
                // Append the nonce to the parameter list.
                $parameters['x'] = "x={$nonce}";
            }

            // Form the request URI from the configured values.
            $request_uri = "cashid:" . $this->service_domain . $this->service_path . "?" . implode($parameters, '&');

            // Store the request and nonce in local cache if not user generated.
            if (!$user_generated && !$this->cache->set("cashid_request_{$nonce}", [ 'available' => true, 'request' => $request_uri, 'expires' => time() + (60 * 15) ])) {
                throw new InternalException("Failed to store request metadata in APCu.");
            }

            // Return the request URI to indicate success.
            return $request_uri;
        } catch (InternalException $exception) {
            // Return false to indicate error.
            return false;
        }
    }

    /**
     * Creates a metadata request string part from a metadata array
     *
     * The metadata array is in the form:
     *
     * $metadata = [
     *    'identification' => [list, of, fields],
     *    'position' => [list, of, fields],
     *    'contact' => [list, of, fields],
     * ]
     *
     * @param array $metadata
     *  Specification for which metadata is requested from the client.
     * @return string
     *  The request metadata part
     */
    private function encodeRequestMetadata(array $metadata): string
    {
        // Initialize an empty metadata string.
        $metadata_string = "";

        // Iterate over the available metadata names.
        foreach (API::METADATA_NAMES as $metadata_type => $metadata_fields) {
            // Store the first letter of the metadata type.
            $metadata_letter = substr($metadata_type, 0, 1);

            // Initialize an empty metadata part string.
            $metadata_part = "";

            //
            if (isset($metadata[$metadata_type])) {
                // Iterate over each field of this metadata type.
                foreach ($metadata_fields as $field_name => $field_code) {
                    // If this field was requested..
                    if (in_array($field_name, $metadata[$metadata_type])) {
                        // .. add it to the metadata part.
                        $metadata_part .= $field_code;
                    }
                }

                // If, after checking for requested metadata of this type, some matches were found..
                if ($metadata_part !== "") {
                    // Add the letter and numbers matching the requested metadata to the metadata string.
                    $metadata_string .= "{$metadata_letter}{$metadata_part}";
                }
            }
        }

        // Return the filled in metadata string.
        return $metadata_string;
    }
}
