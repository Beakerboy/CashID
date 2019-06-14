<?php
// Declare a unique namespace to avoid naming collisions.
namespace CashID;

use BitcoinPHP\BitcoinECDSA\BitcoinECDSA;

/**
 * Simple CashID support library that can:
 * - Issue requests
 * - Verify requests
 * - Send status confirmations
 *
 * Requirements for this library to function:
 * - PHP support for PECL APCu
 * - BitcoinD node with RPC support
 */
class CashID
{
    // Storage for a status confirmation message.
    private static $statusConfirmation;
    
    protected $service_domain;
    protected $service_path;

    // Define regular expressions to parse request data.
    const REGEXP_REQUEST = "/(?P<scheme>cashid:)(?:[\/]{2})?(?P<domain>[^\/]+)(?P<path>\/[^\?]+)(?P<parameters>\?.+)/";
    const REGEXP_PARAMETERS = "/(?:(?:[\?\&]a=)(?P<action>[^\&]+))?(?:(?:[\?\&]d=)(?P<data>[^\&]+))?(?:(?:[\?\&]r=)(?P<required>[^\&]+))?(?:(?:[\?\&]o=)(?P<optional>[^\&]+))?(?:(?:[\?\&]x=)(?P<nonce>[^\&]+))?/";
    const REGEXP_METADATA = "/(i(?P<identification>(?![1-9]+))?(?P<name>1)?(?P<family>2)?(?P<nickname>3)?(?P<age>4)?(?P<gender>5)?(?P<birthdate>6)?(?P<picture>8)?(?P<national>9)?)?(p(?P<position>(?![1-9]+))?(?P<country>1)?(?P<state>2)?(?P<city>3)?(?P<streetname>4)?(?P<streetnumber>5)?(?P<residence>6)?(?P<coordinate>9)?)?(c(?P<contact>(?![1-9]+))?(?P<email>1)?(?P<instant>2)?(?P<social>3)?(?P<mobilephone>4)?(?P<homephone>5)?(?P<workphone>6)?(?P<postlabel>9)?)?/";

    // List of actions that required a valid and recent timestamp as their nonce, instead of a nonce issued by us.
    const USER_ACTIONS = [
        'delete',
        'logout',
        'revoke',
        'update',
    ];

    // List of CashID status codes.
    const STATUS_CODES = [
        'SUCCESSFUL' => 0,
        'REQUEST_BROKEN' => 100,
        'REQUEST_MISSING_SCHEME' => 111,
        'REQUEST_MISSING_DOMAIN' => 112,
        'REQUEST_MISSING_NONCE' => 113,
        'REQUEST_MALFORMED_SCHEME' => 121,
        'REQUEST_MALFORMED_DOMAIN' => 122,
        'REQUEST_INVALID_DOMAIN' => 131,
        'REQUEST_INVALID_NONCE' => 132,
        'REQUEST_ALTERED' => 141,
        'REQUEST_EXPIRED' => 142,
        'REQUEST_CONSUMED' => 143,
        'RESPONSE_BROKEN' => 200,
        'RESPONSE_MISSING_REQUEST' => 211,
        'RESPONSE_MISSING_ADDRESS' => 212,
        'RESPONSE_MISSING_SIGNATURE' => 213,
        'RESPONSE_MISSING_METADATA' => 214,
        'RESPONSE_MALFORMED_ADDRESS' => 221,
        'RESPONSE_MALFORMED_SIGNATURE' => 222,
        'RESPONSE_MALFORMED_METADATA' => 223,
        'RESPONSE_INVALID_METHOD' => 231,
        'RESPONSE_INVALID_ADDRESS' => 232,
        'RESPONSE_INVALID_SIGNATURE' => 233,
        'RESPONSE_INVALID_METADATA' => 234,

        'SERVICE_BROKEN' => 300,
        'SERVICE_ADDRESS_DENIED' => 311,
        'SERVICE_ADDRESS_REVOKED' => 312,
        'SERVICE_ACTION_DENIED' => 321,
        'SERVICE_ACTION_UNAVAILABLE' => 322,
        'SERVICE_ACTION_NOT_IMPLEMENTED' => 323,
        'SERVICE_INTERNAL_ERROR' => 331
    ];

    const METADATA_NAMES = [
        'identity' => [
            'name' => 1,
            'family' => 2,
            'nickname' => 3,
            'age' => 4,
            'gender' => 5,
            'birthdate' => 6,
            'picture' => 8,
            'national' => 9
        ],
        'position' => [
            'country' => 1,
            'state' => 2,
            'city' => 3,
            'streetname' => 4,
            'streetnumber' => 5,
            'residence' => 6,
            'coordinates' => 9,
        ],
        'contact' => [
            'email' => 1,
            'instant' => 2,
            'social' => 3,
            'phone' => 4,
            'postal' => 5
        ],
    ];

    public function __construct(string $domain, string $path)
    {
        $this->service_domain = $domain;
        $this->service_path = $path;
    }
    
    /**
     * Creates a request
     *
     * @param string $action
     *  Name of the action the user authenticates to perform
     * @param string $data
     *  Data relevant to the requested action
     * @param array $metadata
     *  Array with requested and optional metadata
     * @return
     *  returns the request URI or false if error
     */
    public function createRequest(string $action = "", string $data = "", array $metadata = [])
    {
        try {
            // generate a random nonce.
            $nonce = rand(100000000, 999999999);

            // Check if the nonce is already used, and regenerate until it does not exist.
            while (apcu_exists("cashid_request_{$nonce}")) {
                // generate a random nonce.
                $nonce = rand(100000000, 999999999);
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
                $parameters['r'] = "r=" . self::encodeRequestMetadata($metadata['required']);
            }

            // If optional metadata was requested, add them to the parameter list.
            if (isset($metadata['optional'])) {
                $parameters['o'] = "o=" . self::encodeRequestMetadata($metadata['optional']);
            }

            // Append the nonce to the parameter list.
            $parameters['x'] = "x={$nonce}";

            // Form the request URI from the configured values.
            $request_uri = "cashid:" . $this->service_domain . $this->service_path . "?" . implode($parameters, '&');

            // Store the request and nonce in local cache.
            if (!apcu_store("cashid_request_{$nonce}", [ 'available' => true, 'request' => $request_uri, 'expires' => time() + (60 * 15) ])) {
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
     * @param {Array} metadata - Array with requested and optional metadata
     * @return {string} returns the request metadata part
     */
    private function encodeRequestMetadata(array $metadata): string
    {
        // Initialize an empty metadata string.
        $metadata_string = "";

        // Iterate over the available metadata names.
        foreach (self::METADATA_NAMES as $metadata_type => $metadata_fields) {
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

    /**
     * Parses a request string and returns a request array.
     *
     * @param {String} request_url - the full request URI to parse
     * @return {Array} returns a request array populated based on the request_url string
     */
    public function parseRequest(string $request_uri): array
    {
        // Initialize empty structure
        $request_parts = [];

        // Parse the request URI.
        @preg_match(self::REGEXP_REQUEST, $request_uri, $request_parts);
        @preg_match(self::REGEXP_PARAMETERS, $request_parts['parameters'], $request_parts['parameters']);
        @preg_match(self::REGEXP_METADATA, $request_parts['parameters']['required'], $request_parts['parameters']['required']);
        @preg_match(self::REGEXP_METADATA, $request_parts['parameters']['optional'], $request_parts['parameters']['optional']);

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
            if (is_int($key)) {
                unset($request_parts['parameters']['required'][$key]);
            }
        }
        foreach ($request_parts['parameters']['optional'] as $key => $value) {
            if (is_int($key)) {
                unset($request_parts['parameters']['optional'][$key]);
            }
        }

        return $request_parts;
    }

    /**
     * Invalidates the current request with a custom code and message.
     *
     * @param {String} status_code - numerical number for the status code.
     * @param {String} status_message - textual description of the status.
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
     * @return mixed - array or false if error
     */
    public function validateRequest(string $response)
    {
        // Initalized an assumed successful status.
        self::$statusConfirmation = [
            'status' => self::STATUS_CODES['SUCCESSFUL'],
            'message' => '',
        ];

        try {
            // Attempt to decode the response data.
            $responseObject = json_decode($response, true);

            // Validate that the response is JSON encoded.
            if ($responseObject === null) {
                throw new InternalException("Response data is not a valid JSON object.", self::STATUS_CODES['RESPONSE_BROKEN']);
            }

            // Validate if the required field 'request' exists.
            if (!isset($responseObject['request'])) {
                throw new InternalException("Response data is missing required 'request' property.", self::STATUS_CODES['RESPONSE_MISSING_REQUEST']);
            }

            // Validate if the required field 'address' exists.
            if (!isset($responseObject['address'])) {
                throw new InternalException("Response data is missing required 'address' property.", self::STATUS_CODES['RESPONSE_MISSING_ADDRESS']);
            }

            // Validate if the required field 'signature' exists.
            if (!isset($responseObject['signature'])) {
                throw new InternalException("Response data is missing required 'signature' property.", self::STATUS_CODES['RESPONSE_MISSING_SIGNATURE']);
            }

            // Parse the request.
            $parsedRequest = self::parseRequest($responseObject['request']);

            // Validate overall structure.
            if ($parsedRequest === false) {
                throw new InternalException("Internal server error, could not evaluate request structure.", self::STATUS_CODES['SERVICE_INTERNAL_ERROR']);
            } elseif ($parsedRequest == 0) {
                throw new InternalException("Request URI is invalid.", self::STATUS_CODES['REQUEST_BROKEN']);
            }

            // Validate the request scheme.
            if (!isset($parsedRequest['scheme'])) {
                throw new InternalException("Request scheme '{$parsedRequest['scheme']}' is invalid, should be 'cashid:'.", self::STATUS_CODES['REQUEST_MALFORMED_SCHEME']);
            }

            // TODO: Validate the domain structure.

            // Validate the request domain.
            if ($parsedRequest['domain'] != $this->service_domain) {
                throw new InternalException("Request domain '{$parsedRequest['domain']}' is invalid, this service uses '" . $this->service_domain . "'.", self::STATUS_CODES['REQUEST_INVALID_DOMAIN']);
            }

            // Validate the parameter structure
            if ($parsedRequest['parameters'] === false) {
                throw new InternalException("Internal server error, could not evaluate request parameters.", self::STATUS_CODES['SERVICE_INTERNAL_ERROR']);
            } elseif ($parsedRequest['parameters'] == 0) {
                throw new InternalException("Request parameters are invalid.", self::STATUS_CODES['REQUEST_BROKEN']);
            }

            // Validate the existance of a nonce.
            if (!isset($parsedRequest['parameters']['nonce'])) {
                throw new InternalException("Request parameter 'nonce' is missing.", self::STATUS_CODES['REQUEST_MISSING_NONCE']);
            }

            // Locally store if the request action is a user-initiated action.
            $user_initiated_request = isset(self::USER_ACTIONS[$parsedRequest['parameters']['action']]);

            // Locally store values to compare with nonce timestamp to validate recency.
            // NOTE: current time is set to 1 minute in the future to allow for minor clock drift.
            $recent_time = (time() - (60 * 60 * 15));
            $current_time = (time() + (60 * 1 * 1));

            // TODO: Separate MALFORMED (valid timestamp) from INVALID (not recent) for timestamp.

            // Validate if a user initiated request is a recent and valid timestamp...
            if ($user_initiated_request and (($parsedRequest['parameters']['nonce'] < $recent_time) or ($parsedRequest['parameters']['nonce'] > $current_time))) {
                throw new InternalException("Request nonce for user initated action is not a valid and recent timestamp.", self::STATUS_CODES['REQUEST_INVALID_NONCE']);
            }

            // Try to load the request from the apcu object cache.
            $requestReference = apcu_fetch("cashid_request_{$parsedRequest['parameters']['nonce']}");

            // Validate that the request was issued by this service provider.
            if (!$user_initiated_request and ($requestReference === false)) {
                throw new InternalException("The request nonce was not issued by this service.", self::STATUS_CODES['REQUEST_INVALID_NONCE']);
            }

            // Validate if the request is available
            if (!$user_initiated_request and ($requestReference['available'] === false)) {
                throw new InternalException("The request nonce was not issued by this service.", self::STATUS_CODES['NONCE_CONSUMED']);
            }

            // Validate if the request has expired.
            if (!$user_initiated_request and ($requestReference['expires'] < time())) {
                throw new InternalException("The request has expired and is no longer available.", self::STATUS_CODES['REQUEST_EXPIRED']);
            }

            // Validate that the request has not been tampered with.
            if (!$user_initiated_request and ($requestReference['request'] != $responseObject['request'])) {
                throw new InternalException("The response does not match the request parameters.", self::STATUS_CODES['REQUEST_ALTERED']);
            }

            // Send the request parts to bitcoind for signature verification.
            $bitcoinECDSA = new BitcoinECDSA();
            $verificationStatus = $bitcoinECDSA->checkSignatureForMessage($responseObject['address'], $responseObject['signature'], $responseObject['request']);
            //$verificationStatus = self::verifymessage($responseObject['address'], $responseObject['signature'], $responseObject['request']);

            // Validate the signature.
            if ($verificationStatus !== true) {
                throw new InternalException("Signature verification failed: {self::$rpc_error}", self::STATUS_CODES['RESPONSE_INVALID_SIGNATURE']);
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
                throw new InternalException("The required metadata field(s) '" . implode(', ', $missing_fields) . "' was not provided.", self::STATUS_CODES['RESPONSE_MISSING_METADATA']);
            }

            // Loop over the supplied metadata fields.
            foreach ($responseObject['metadata'] as $metadata_name => $metadata_value) {
                // Validate if the supplied metadata was requested
                if (!isset($parsedRequest['parameters']['required'][$metadata_name]) and !isset($parsedRequest['parameters']['optional'][$metadata_name])) {
                    throw new InternalException("The metadata field '{$metadata_name}' was not part of the request.", self::STATUS_CODES['RESPONSE_INVALID_METADATA']);
                }

                // Validate if the supplied value is empty.
                if ($metadata_value == "" or $metadata_value === null) {
                    throw new InternalException("The metadata field '{$metadata_name}' did not contain any value.", self::STATUS_CODES['RESPONSE_MALFORMED_METADATA']);
                }
            }

            // Store the response object in local cache.
            if (!apcu_store("cashid_response_{$parsedRequest['parameters']['nonce']}", $responseObject)) {
                throw new InternalException("Internal server error, could not store response object.", self::STATUS_CODES['SERVICE_INTERNAL_ERROR']);
            }

            // Store the confirmation object in local cache.
            if (!apcu_store("cashid_confirmation_{$parsedRequest['parameters']['nonce']}", self::$statusConfirmation)) {
                throw new InternalException("Internal server error, could not store confirmation object.", self::STATUS_CODES['SERVICE_INTERNAL_ERROR']);
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
            throw new \Exception('cashid->confirm_request was called after data had been transmitted to the client, which prevents setting the required headers.');
        }

        // Sanity check if validation has not yet been done.
        if (!isset(self::$statusConfirmation['status'])) {
            throw new \Exception('cashid->confirm_request was called before validate_request so there is no confirmation to transmit to the client.');
        }

        // Configure confirmation message type.
        header('Content-type: application/json; charset=utf-8');
        header('Cache-Control: no-cache');

        // send the response confirmation back to the identity manager.
        echo json_encode(self::$statusConfirmation);
    }
}
