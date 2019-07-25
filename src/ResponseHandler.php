<?php

namespace CashID;

use CashID\Cache\RequestCacheInterface;
use CashID\Cache\APCuCache;
use CashID\Notary\NotaryInterface;
use CashID\Notary\DefaultNotary;

/**
 * ResponseHandler
 *
 * The ResponseHandler class validates a CashID response and produces an
 * appropriate confirmation or error code.
 */
class ResponseHandler
{
    // Storage for a status confirmation message.
    private static $statusConfirmation;
    
    protected $service_domain;
    protected $service_path;
    protected $notary;
    protected $cache;

    public function __construct(string $domain, string $path, NotaryInterface $notary = null, RequestCacheInterface $cache = null)
    {
        $this->service_domain = $domain;
        $this->service_path = $path;
        $this->notary = $notary ?? new DefaultNotary();
        $this->cache = $cache ?? new APCuCache();
    }

    /**
     * Parses a request string and returns a request array.
     *
     * @param string $request_uri
     *   the full request URI to parse
     * @return array
     *   returns a request array populated based on the request_url string
     */
    public static function parseRequest(string $request_uri): array
    {
        // Initialize empty structure
        $request_parts = [];

        // Parse the request URI.
        @preg_match(API::REGEXP_REQUEST, $request_uri, $request_parts);
        @preg_match(API::REGEXP_PARAMETERS, $request_parts['parameters'], $request_parts['parameters']);
        @preg_match(API::REGEXP_METADATA, $request_parts['parameters']['required'], $request_parts['parameters']['required']);
        @preg_match(API::REGEXP_METADATA, $request_parts['parameters']['optional'], $request_parts['parameters']['optional']);

        // TODO: Make this pretty. It removes the numeric index that preg_match makes despite named group matching.
        foreach ($request_parts as $key => $value) {
            if (is_int($key)) {
                unset($request_parts[$key]);
            }
        }
        foreach ($request_parts['parameters'] as $key => $value) {
            if (is_int($key)) {
                unset($request_parts['parameters'][$key]);
            }
        }
        foreach ($request_parts['parameters']['required'] as $key => $value) {
            if (is_int($key) || $request_parts['parameters']['required'][$key] === '') {
                unset($request_parts['parameters']['required'][$key]);
            }
        }
        foreach ($request_parts['parameters']['optional'] as $key => $value) {
            if (is_int($key) || $request_parts['parameters']['optional'][$key] === '') {
                unset($request_parts['parameters']['optional'][$key]);
            }
        }

        return $request_parts;
    }

    /**
     * Invalidates the current request with a custom code and message.
     *
     * @param string $status_code
     *   numerical number for the status code.
     * @param string $status_message
     *   textual description of the status.
     */
    public function invalidateRequest(string $status_code, string $status_message)
    {
        self::$statusConfirmation = [
            'status' => $status_code,
            'message' => $status_message
        ];
    }

    /**
     * Validates the current request and updates the internal confirmation message.
     *
     * @return mixed
     *   array or false if error
     */
    public function validateRequest(string $response)
    {
        // Initalized an assumed successful status.
        self::$statusConfirmation = [
            'status' => API::STATUS_CODES['SUCCESSFUL'],
            'message' => '',
        ];

        try {
            // Attempt to decode the response data.
            $responseObject = json_decode($response, true);

            // Validate that the response is JSON encoded.
            if ($responseObject === null) {
                throw new InternalException("Response data is not a valid JSON object.", API::STATUS_CODES['RESPONSE_BROKEN']);
            }

            // Validate if the required field 'request' exists.
            if (!isset($responseObject['request'])) {
                throw new InternalException("Response data is missing required 'request' property.", API::STATUS_CODES['RESPONSE_MISSING_REQUEST']);
            }

            // Validate if the required field 'address' exists.
            if (!isset($responseObject['address'])) {
                throw new InternalException("Response data is missing required 'address' property.", API::STATUS_CODES['RESPONSE_MISSING_ADDRESS']);
            }

            // Validate if the required field 'signature' exists.
            if (!isset($responseObject['signature'])) {
                throw new InternalException("Response data is missing required 'signature' property.", API::STATUS_CODES['RESPONSE_MISSING_SIGNATURE']);
            }

            // Parse the request.
            $parsedRequest = self::parseRequest($responseObject['request']);

            // Validate overall structure.
            if (!$parsedRequest) {
                throw new InternalException("Internal server error, could not evaluate request structure.", API::STATUS_CODES['SERVICE_INTERNAL_ERROR']);
            } elseif ($parsedRequest == 0) {
                throw new InternalException("Request URI is invalid.", API::STATUS_CODES['REQUEST_BROKEN']);
            }

            // Validate the request scheme.
            if ($parsedRequest['scheme'] !== 'cashid:') {
                throw new InternalException("Request scheme '{$parsedRequest['scheme']}' is invalid, should be 'cashid:'.", API::STATUS_CODES['REQUEST_MALFORMED_SCHEME']);
            }

            // TODO: Validate the domain structure.

            // Validate the request domain.
            if ($parsedRequest['domain'] != $this->service_domain) {
                throw new InternalException("Request domain '{$parsedRequest['domain']}' is invalid, this service uses '" . $this->service_domain . "'.", API::STATUS_CODES['REQUEST_INVALID_DOMAIN']);
            }

            // Validate the parameter structure
            if ($parsedRequest['parameters'] === false) {
                throw new InternalException("Internal server error, could not evaluate request parameters.", API::STATUS_CODES['SERVICE_INTERNAL_ERROR']);
            } elseif ($parsedRequest['parameters'] == 0) {
                throw new InternalException("Request parameters are invalid.", API::STATUS_CODES['REQUEST_BROKEN']);
            }

            // Validate the existance of a nonce.
            if (!isset($parsedRequest['parameters']['nonce'])) {
                throw new InternalException("Request parameter 'nonce' is missing.", API::STATUS_CODES['REQUEST_MISSING_NONCE']);
            }

            // Locally store if the request action is a user-initiated action.
            $user_initiated_request = in_array($parsedRequest['parameters']['action'], API::USER_ACTIONS);

            // Locally store values to compare with nonce timestamp to validate recency.
            // NOTE: current time is set to 1 minute in the future to allow for minor clock drift.
            $recent_time = (time() - (60 * 60 * 15));
            $current_time = (time() + (60 * 1 * 1));

            // TODO: Separate MALFORMED (valid timestamp) from INVALID (not recent) for timestamp.

            // Validate if a user initiated request is a recent and valid timestamp...
            if ($user_initiated_request and (($parsedRequest['parameters']['nonce'] < $recent_time) or ($parsedRequest['parameters']['nonce'] > $current_time))) {
                throw new InternalException("Request nonce for user initated action is not a valid and recent timestamp.", API::STATUS_CODES['REQUEST_INVALID_NONCE']);
            }

            // Try to load the request from the object cache.
            $requestReference = $this->cache->fetch("cashid_request_{$parsedRequest['parameters']['nonce']}");

            // Validate that the request was issued by this service provider.
            if (!$user_initiated_request and (!$requestReference)) {
                throw new InternalException("The request nonce was not issued by this service.", API::STATUS_CODES['REQUEST_INVALID_NONCE']);
            }

            // Validate if the request is available
            if (!$user_initiated_request and ($requestReference['available'] === false)) {
                throw new InternalException("The request nonce was not issued by this service.", API::STATUS_CODES['REQUEST_CONSUMED']);
            }

            // Validate if the request has expired.
            if (!$user_initiated_request and ($requestReference['expires'] < time())) {
                throw new InternalException("The request has expired and is no longer available.", API::STATUS_CODES['REQUEST_EXPIRED']);
            }

            // Validate that the request has not been tampered with.
            if (!$user_initiated_request and ($requestReference['request'] != $responseObject['request'])) {
                throw new InternalException("The response does not match the request parameters.", API::STATUS_CODES['REQUEST_ALTERED']);
            }

            // Send the request parts to the notary for signature verification.
            $verificationStatus = $this->notary->checkSignature($responseObject['address'], $responseObject['signature'], $responseObject['request']);

            // Validate the signature.
            if ($verificationStatus !== true) {
                throw new InternalException("Signature verification failed.", API::STATUS_CODES['RESPONSE_INVALID_SIGNATURE']);
            }

            // Initialize an empty list of missing metadata.
            $missing_fields = [];

            // Loop over the required metadata fields.
            foreach ($parsedRequest['parameters']['required'] as $metadata_name => $metadata_value) {
                // If the field was required and missing from the response..
                if (($metadata_value) and (!isset($responseObject['metadata'][$metadata_name]))) {
                    // Store it in the list of missing fields.
                    $missing_fields[$metadata_name] = $metadata_name;
                }
            }

            // Validate if there was missing metadata.
            if (count($missing_fields) >= 1) {
                throw new InternalException("The required metadata field(s) '" . implode(', ', $missing_fields) . "' was not provided.", API::STATUS_CODES['RESPONSE_MISSING_METADATA']);
            }

            // Loop over the supplied metadata fields.
            if (isset($responseObject['metadata'])) {
                foreach ($responseObject['metadata'] as $metadata_name => $metadata_value) {
                    // Validate if the supplied metadata was requested
                    if (!isset($parsedRequest['parameters']['required'][$metadata_name]) and !isset($parsedRequest['parameters']['optional'][$metadata_name])) {
                        throw new InternalException("The metadata field '{$metadata_name}' was not part of the request.", API::STATUS_CODES['RESPONSE_INVALID_METADATA']);
                    }

                    // Validate if the supplied value is empty.
                    if ($metadata_value == "" or $metadata_value === null) {
                        throw new InternalException("The metadata field '{$metadata_name}' did not contain any value.", API::STATUS_CODES['RESPONSE_MALFORMED_METADATA']);
                    }
                }
            }

            // Store the response object in local cache.
            if (!$this->cache->store("cashid_response_{$parsedRequest['parameters']['nonce']}", $responseObject)) {
                throw new InternalException("Internal server error, could not store response object.", API::STATUS_CODES['SERVICE_INTERNAL_ERROR']);
            }

            // Store the confirmation object in local cache.
            if (!$this->cache->store("cashid_confirmation_{$parsedRequest['parameters']['nonce']}", self::$statusConfirmation)) {
                throw new InternalException("Internal server error, could not store confirmation object.", API::STATUS_CODES['SERVICE_INTERNAL_ERROR']);
            }

            // Add the action and data parameters to the response structure.
            $responseObject['action'] = (isset($parsedRequest['action']) ? $parsedRequest['action'] : 'auth');
            $responseObject['data'] = (isset($parsedRequest['data']) ? $parsedRequest['data'] : '');

            // Return the parsed response.
            return $responseObject;
        } catch (InternalException $exception) {
            // Update internal status object.
            self::$statusConfirmation = [
                'status' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ];

            // Return false to indicate error.
            return false;
        }
    }

    /**
     * Sends the internal confirmation to the identity manager.
     */
    public function confirmRequest()
    {
        // Sanity check if headers have already been sent.
        if (headers_sent()) {
            throw new \Exception('cashid->confirmRequest was called after data had been transmitted to the client, which prevents setting the required headers.');
        }

        // Sanity check if validation has not yet been done.
        if (!isset(self::$statusConfirmation['status'])) {
            throw new \Exception('cashid->confirmRequest was called before validateRequest so there is no confirmation to transmit to the client.');
        }

        // Configure confirmation message type.
        header('Content-type: application/json; charset=utf-8');
        header('Cache-Control: no-cache');

        // send the response confirmation back to the identity manager.
        echo json_encode(self::$statusConfirmation);
    }
}
