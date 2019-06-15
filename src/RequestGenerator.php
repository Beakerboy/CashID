<?php

namespace CashID;

/**
 * Simple CashID support library that can issue requests.
 */
class RequestGenrator
{
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
}
