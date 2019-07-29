<?php

namespace CashID;

use CashID\Exceptions\InternalException;

class API
{
   // Define regular expressions to parse request data.
    const REGEXP_REQUEST = "/(?P<scheme>.*:)(?:[\/]{2})?(?P<domain>[^\/]+)(?P<path>\/[^\?]+)(?P<parameters>\?.+)/";
    const REGEXP_PARAMETERS = "/(?:(?:[\?\&]a=)(?P<action>[^\&]+))?(?:(?:[\?\&]d=)(?P<data>[^\&]+))?(?:(?:[\?\&]r=)(?P<required>[^\&]+))?(?:(?:[\?\&]o=)(?P<optional>[^\&]+))?(?:(?:[\?\&]x=)(?P<nonce>[^\&]+))?/";
    const REGEXP_METADATA = "/(i(?P<identification>(?![1-9]+))?(?P<name>1)?(?P<family>2)?(?P<nickname>3)?(?P<age>4)?(?P<gender>5)?(?P<birthdate>6)?(?P<picture>8)?(?P<national>9)?)?(p(?P<position>(?![1-9]+))?(?P<country>1)?(?P<state>2)?(?P<city>3)?(?P<streetname>4)?(?P<streetnumber>5)?(?P<residence>6)?(?P<coordinate>9)?)?(c(?P<contact>(?![1-9]+))?(?P<email>1)?(?P<instant>2)?(?P<social>3)?(?P<mobilephone>4)?(?P<homephone>5)?(?P<workphone>6)?(?P<postlabel>9)?)?/";

    // List of actions that require a valid and recent timestamp as their nonce, instead of a nonce issued by us.
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
        'identification' => [
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
}
