<?php

namespace CashID\Services;

use CashID\API;
use CashID\Exceptions\InternalException;
use CashID\Exceptions\CashIDException;
use CashID\Notary\NotaryInterface;

/**
 * ResponseHandler
 *
 * The ResponseHandler class validates a CashID response and produces an
 * appropriate confirmation or error code.
 */
class ResponseHandler extends CashIDService
{
    // Storage for a status confirmation message.
    private static $statusConfirmation;

    // Default dependencies
    protected $defaultDependencies = [
        'CashID\Notary\NotaryInterface' => ['name' => 'notary', 'class' => '\CashID\Notary\DefaultNotary'],
    ];

    protected $notary;

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
            $required_fields = [
                'request' => 'RESPONSE_MISSING_REQUEST',
                'address' => 'RESPONSE_MISSING_ADDRESS',
                'signature' => 'RESPONSE_MISSING_SIGNATURE',
            ];
            // Validate if the required fields exist.
            foreach ($required_fields as $field => $code_name) {
                if (!isset($responseObject[$field])) {
                    $message = "Response data is missing required $field property.";
                    $code = API::STATUS_CODES[$code_name];
                    throw new InternalException($message, $code);
                }
            }

            // Parse the request.
            $parsedRequest = API::parseRequest($responseObject['request']);

            // Validate overall structure.
            if (!$parsedRequest) {
                $message = "Internal server error, could not evaluate request structure.";
                $code = API::STATUS_CODES['SERVICE_INTERNAL_ERROR'];
                throw new InternalException($message, $code);
            } elseif ($parsedRequest == 0) {
                throw new InternalException("Request URI is invalid.", API::STATUS_CODES['REQUEST_BROKEN']);
            }

            // Validate the request scheme.
            if ($parsedRequest['scheme'] !== 'cashid:') {
                $message = "Request scheme '{$parsedRequest['scheme']}' is invalid, should be 'cashid:'.";
                $code = API::STATUS_CODES['REQUEST_MALFORMED_SCHEME'];
                throw new InternalException($message, $code);
            }

            // TODO: Validate the domain structure.

            // Validate the request domain.
            if ($parsedRequest['domain'] != $this->service_domain) {
                $message = "Request domain '{$parsedRequest['domain']}' is invalid, this service uses '" . $this->service_domain . "'.";
                $code = API::STATUS_CODES['REQUEST_INVALID_DOMAIN'];
                throw new InternalException($message, $code);
            }

            // Validate the parameter structure
            if ($parsedRequest['parameters'] === false) {
                $message = "Internal server error, could not evaluate request parameters.";
                $code = API::STATUS_CODES['SERVICE_INTERNAL_ERROR'];
                throw new InternalException($message, $code);
            } elseif ($parsedRequest['parameters'] == 0) {
                throw new InternalException("Request parameters are invalid.", API::STATUS_CODES['REQUEST_BROKEN']);
            }

            // Validate the existance of a nonce.
            if (!isset($parsedRequest['parameters']['nonce'])) {
                $message = "Request parameter 'nonce' is missing.";
                $code = API::STATUS_CODES['REQUEST_MISSING_NONCE'];
                throw new InternalException($message, $code);
            }

            // Locally store if the request action is a user-initiated action.
            $user_initiated_request = in_array($parsedRequest['parameters']['action'], API::USER_ACTIONS);

            // Locally store values to compare with nonce timestamp to validate recency.
            // NOTE: current time is set to 1 minute in the future to allow for minor clock drift.
            $recent_time = (time() - (60 * 60 * 15));
            $current_time = (time() + (60 * 1 * 1));

            // TODO: Separate MALFORMED (valid timestamp) from INVALID (not recent) for timestamp.

            $nonce = $parsedRequest['parameters']['nonce'];
            // Validate if a user initiated request is a recent and valid timestamp...
            if ($user_initiated_request && (($nonce < $recent_time) or ($nonce > $current_time))) {
                $message = "Request nonce for user initated action is not a valid and recent timestamp.";
                $code = API::STATUS_CODES['REQUEST_INVALID_NONCE'];
                throw new InternalException($message, $code);
            }

            // Try to load the request from the object cache.
            $requestReference = $this->cache->get("cashid_request_{$nonce}");

            // Validate that the request was issued by this service provider.
            if (!$user_initiated_request && !$requestReference) {
                $message = "The request nonce was not issued by this service.";
                $code = API::STATUS_CODES['REQUEST_INVALID_NONCE'];
                throw new InternalException($message, $code);
            }

            // Validate if the request is available
            if (!$user_initiated_request && ($requestReference['available'] === false)) {
                $message = "The request has been used and is no longer available.";
                $code = API::STATUS_CODES['REQUEST_CONSUMED'];
                throw new InternalException($message, $code);
            }

            // Validate if the request has expired.
            if (!$user_initiated_request && ($requestReference['expires'] < time())) {
                $message = "The request has expired and is no longer available.";
                $code = API::STATUS_CODES['REQUEST_EXPIRED'];
                throw new InternalException($message, $code);
            }

            // Validate that the request has not been tampered with.
            if (!$user_initiated_request && ($requestReference['request'] != $responseObject['request'])) {
                $message = "The response does not match the request parameters.";
                $code = API::STATUS_CODES['REQUEST_ALTERED'];
                throw new InternalException($message, $code);
            }

            // Send the request parts to the notary for signature verification.
            $address = $responseObject['address'];
            $signature = $responseObject['signature'];
            $request = $responseObject['request'];
            $verificationStatus = $this->notary->checkSignature($address, $signature, $request);

            // Validate the signature.
            if ($verificationStatus !== true) {
                $message = "Signature verification failed.";
                $code = API::STATUS_CODES['RESPONSE_INVALID_SIGNATURE'];
                throw new InternalException($message, $code);
            }

            // Initialize an empty list of missing metadata.
            $missing_fields = [];

            // Loop over the required metadata fields.
            foreach ($parsedRequest['parameters']['required'] as $metadata_name => $metadata_value) {
                // If the field was required and missing from the response..
                if ($metadata_value && !isset($responseObject['metadata'][$metadata_name])) {
                    // Store it in the list of missing fields.
                    $missing_fields[$metadata_name] = $metadata_name;
                }
            }

            // Validate if there was missing metadata.
            if (count($missing_fields) >= 1) {
                $message = "The required metadata field(s) '" . implode(', ', $missing_fields) . "' was not provided.";
                $code = API::STATUS_CODES['RESPONSE_MISSING_METADATA'];
                throw new InternalException($message, $code);
            }

            // Loop over the supplied metadata fields.
            if (isset($responseObject['metadata'])) {
                foreach ($responseObject['metadata'] as $metadata_name => $metadata_value) {
                    // Validate if the supplied metadata was requested
                    if (!isset($parsedRequest['parameters']['required'][$metadata_name]) && !isset($parsedRequest['parameters']['optional'][$metadata_name])) {
                        $message = "The metadata field '{$metadata_name}' was not part of the request.";
                        $code = API::STATUS_CODES['RESPONSE_INVALID_METADATA'];
                        throw new InternalException($message, $code);
                    }

                    // Validate if the supplied value is empty.
                    if (($metadata_value == "") || ($metadata_value === null)) {
                        $message = "The metadata field '{$metadata_name}' did not contain any value.";
                        $code = API::STATUS_CODES['RESPONSE_MALFORMED_METADATA'];
                        throw new InternalException($message, $code);
                    }
                }
            }

            // Store the response object in local cache.
            if (!$this->cache->set("cashid_response_{$nonce}", $responseObject)) {
                $message = "Internal server error, could not store response object.";
                $code = API::STATUS_CODES['SERVICE_INTERNAL_ERROR'];
                throw new InternalException($message, $code);
            }

            // Store the confirmation object in local cache.
            if (!$this->cache->set("cashid_confirmation_{$nonce}", self::$statusConfirmation)) {
                $message = "Internal server error, could not store confirmation object.";
                $code = API::STATUS_CODES['SERVICE_INTERNAL_ERROR'];
                throw new InternalException($message, $code);
            }

            // Alter the key to make it no longer available
            if (!$this->cache->delete("cashid_request_{$nonce}") || !$this->cache->set("cashid_request_{$nonce}", [ 'available' => false ])) {
                $message = "Internal server error, could not alter request object.";
                $code = API::STATUS_CODES['SERVICE_INTERNAL_ERROR'];
                throw new InternalException($message, $code);
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
            $message = 'cashid->confirmRequest was called after data had been transmitted to the client, which prevents setting the required headers.';
            throw new CashIDException($message);
        }

        // Sanity check if validation has not yet been done.
        if (!isset(self::$statusConfirmation['status'])) {
            $message = 'cashid->confirmRequest was called before validateRequest so there is no confirmation to transmit to the client.';
            throw new CashIDException($message);
        }

        // Configure confirmation message type.
        header('Content-type: application/json; charset=utf-8');
        header('Cache-Control: no-cache');

        // send the response confirmation back to the identity manager.
        echo json_encode(self::$statusConfirmation);
    }
}
