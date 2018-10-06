<?php
	//
	namespace CashID;

	// Create an internal exception type which lets us catch our own exceptions 
	// while still passing on system exceptions that we didn't handle.
	class InternalException extends \Exception {}

	/**
	 * Simple CashID support library that can:
	 * - Issue requests
	 * - Verify requests
	 * - Send status confirmations
	 *
	 * Requirements for this library to function:
	 * - PHP support for PECL APCu
	 * - BitcoinD node with RPC support
	 **/
	class CashID
	{
		// Location for a CashID response manager.
		private $domain = 'demo.cashid.info';
		private $path = "/api/parse.php";

		// Credentials that grant access to a bitcoind RPC connection.
		private $rpc_username = 'uvzOQgLc4VujgDfVpNsfujqasVjVQHhB';
		private $rpc_password = '1Znrf7KClQjJ3AhxDwr7vkFZpwW0ZGUJ';

		// Location of a bitcoind RCP service.
		private $rpc_scheme = 'http://';
		private $rpc_hostname = '127.0.0.1';
		private $rpc_portnumber = 8332;

		// Functional URL and request counter.
		private $rpc_url;
		private $rpc_request_id = 1;

		// Storage for errors caused by RPC calls.
		private  $rpc_error = '';

		// Storage for a status confirmation message.
		private $statusConfirmation;

		// Define regular expressions to parse request data.
		const REGEXP_REQUEST = "/(?P<scheme>cashid:)(?:[\/]{2})?(?P<domain>[^\/]+)(?P<path>\/[^\?]+)(?P<parameters>\?.+)/";
		const REGEXP_PARAMETERS = "/(?:(?:[\?\&]a=)(?P<action>[^\&]+))?(?:(?:[\?\&]d=)(?P<data>[^\&]+))?(?:(?:[\?\&]r=)(?P<required>[^\&]+))?(?:(?:[\?\&]o=)(?P<optional>[^\&]+))?(?:(?:[\?\&]x=)(?P<nonce>[^\&]+))?/";
		const REGEXP_METADATA = "/(i(?P<identification>(?![1-9]+))?(?P<name>1)?(?P<family>2)?(?P<nickname>3)?(?P<age>4)?(?P<gender>5)?(?P<birthdate>6)?(?P<picture>8)?(?P<national>9)?)?(p(?P<position>(?![1-9]+))?(?P<country>1)?(?P<state>2)?(?P<city>3)?(?P<streetname>4)?(?P<streetnumber>5)?(?P<residence>6)?(?P<coordinate>9)?)?(c(?P<contact>(?![1-9]+))?(?P<email>1)?(?P<instant>2)?(?P<social>3)?(?P<mobilephone>4)?(?P<homephone>5)?(?P<workphone>6)?(?P<postlabel>9)?)?/";
		
		// List of actions that required a valid and recent timestamp as their nonce, instead of a nonce issued by us.
		const USER_ACTIONS =
		[
			'delete',
			'logout',
			'revoke',
			'update'
		];

		// List of CashID status codes.
		const STATUS_CODES = 
		[
			'SUCCESSFUL' => 0,

			'MALFORMED_RESPONSE' => 1,
			'MALFORMED_REQUEST' => 2,
			'MALFORMED_ADDRESS' => 3,
			'MALFORMED_SIGNATURE' => 4,
			'MALFORMED_METADATA' => 5,
			'NONCE_INVALID' => 6,
			'NONCE_EXPIRED' => 7,
			'NONCE_CONSUMED' => 8,
			'INVALID_SIGNATURE' => 9,
			'ADDRESS_DENIED' => 10,
			'ADDRESS_REVOKED' => 11,
			'METADATA_MISSING_FIELD' => 12,
			'METADATA_INVALID_FIELD' => 13,
			'ACTION_NOT_IMPLEMENTED' => 14,
			'ACTION_UNAVAILABLE' => 15,
			'ACTION_DENIED' => 16,

			'INTERNAL_ERROR' => 99,
		];

		//
		const METADATA_NAMES =
		[
			'identity' =>
			[
				'name' => 1,
				'family' => 2,
				'nickname' => 3,
				'age' => 4,
				'gender' => 5,
				'birthdate' => 6,
				'picture' => 8,
				'national' => 9
			],
			'position' =>
			[
				'country' => 1,
				'state' => 2,
				'city' => 3,
				'streetname' => 4,
				'streetnumber' => 5,
				'residence' => 6,
				'coordinates' => 9,
			],
			'contact' =>
			[
				'email' => 1,
				'instant' => 2,
				'social' => 3,
				'phone' => 4,
				'postal' => 5
			]
		];

		//
		function __construct()
		{
			// Form the RPC URL from the configured settings.
			$this->rpc_url = "{$this->rpc_scheme}{$this->rpc_username}:{$this->rpc_password}@{$this->rpc_hostname}:{$this->rpc_portnumber}";
		}

		// Convert missing functions to RPC calls.
		function __call($function, $arguments)
		{
			// Remove array keys to simplify argument list?
			$rpc_arguments = array_values($arguments);

			// Convert underscores to spaces, and force lowercase in function names.
			$rpc_function = str_replace('_', '', strtolower($function));

			// Set up an RPC request.
			$rpc_request = 
			[
				'jsonrpc' => '2.0',
				'id' => $this->rpc_request_id++,
				'method' => $rpc_function,
				'params' => $rpc_arguments
			];

			// Set up a stream request.
			$stream_request = 
			[
				'http' =>
				[
					'method' => 'POST',
					'header' => 'Content-type: application/json',
					'content' => json_encode($rpc_request),
					'ignore_errors' => true
				]
			];

			// Create a connection with the RCP host.
			$stream_context = stream_context_create($stream_request);

			// Send the request and store the full response.
			$rpc_response = file_get_contents($this->rpc_url, false, $stream_context);

			// Validate if the request was completed
			if($rpc_response === false)
			{
				// Store error description
				$this->rpc_error = 'Unable to complete RPC request.';

				// Return NULL to indicate that an error was encountered.
				return NULL;
			}
			else
			{
				// Attempt to decode the RCP response
				$object = @json_decode($rpc_response, true);

				// Validate if the RPC response is a valid JSON string.
				if($object === NULL)
				{
					// Store error description
					$this->rpc_error = 'Reply from RPC host was not a valid JSON string.';

					// Return NULL to indicate that an error was encountered.
					return NULL;
				}
				else
				{
					// Check if the response contains any errors.
					if($object['error'])
					{
						// Store the response error message.
						$this->rpc_error = $object['error']['message'];

						// Return NULL to indicate that an error was encountered.
						return NULL;
					}
					else
					{
						// Clear any previous error descriptions.
						$this->rpc_error = '';

						// Return the RPC response data.
						return $object['result'];
					}
				}
			}
		}

		//
		public function create_request($action = "", $data = "", $metadata = [])
		{
			try
			{
				// generate a random nonce.
				$nonce = rand(100000000, 999999999);

				// Check if the nonce is already used, and regenerate until it does not exist.
				while(apcu_exists("cashid_request_{$nonce}"))
				{
					// generate a random nonce.
					$nonce = rand(100000000, 999999999);
				}

				// Initialize an empty parameter list.
				$parameters = [];

				// If a specific action was requested, add it to the parameter list.
				if($action)
				{
					$parameters['a'] = "a={$action}";
				}

				// If specific data was requested, add it to the parameter list.
				if($data)
				{
					$parameters['d'] = "d={$data}";
				}

				// If required metadata was requested, add them to the parameter list.
				if(isset($metadata['required']))
				{
					$parameters['r'] = "r=" . $this->encode_request_metadata($metadata['required']);
				}

				// If optional metadata was requested, add them to the parameter list.
				if(isset($metadata['optional']))
				{
					$parameters['o'] = "o=" . $this->encode_request_metadata($metadata['optional']);
				}

				// Append the nonce to the parameter list.
				$parameters['x'] = "x={$nonce}";

				// Form the request URI from the configured values.
				$request_uri = "cashid:{$this->domain}{$this->path}?" . implode($parameters, '&');

				// Store the request and nonce in local cache.
				if(!apcu_store("cashid_request_{$nonce}", [ 'available' => true, 'expires' => time() + (60 * 15) ]))
				{
					throw new InternalException("Failed to store request metadata in APCu.");
				}

				// Return the request URI to indicate success.
				return $request_uri;
			}
			catch(InternalException $exception)
			{
				// Return false to indicate error.
				return false;
			}
		}

		//
		private function encode_request_metadata($metadata)
		{
			// Initialize an empty metadata string.
			$metadata_string = "";

			// Iterate over the available metadata names.
			foreach(self::METADATA_NAMES as $metadata_type => $metadata_fields)
			{
				// Store the first letter of the metadata type.
				$metadata_letter = substr($metadata_type, 0, 1);

				// Initialize an empty metadata part string.
				$metadata_part = "";

				//
				if(isset($metadata[$metadata_type]))
				{
					// Iterate over each field of this metadata type.
					foreach($metadata_fields as $field_name => $field_code)
					{
						// If this field was requested..
						if(in_array($field_name, $metadata[$metadata_type]))
						{
							// .. add it to the metadata part.
							$metadata_part .= $field_code;
						}
					}

					// If, after checking for requested metadata of this type, some matches were found..
					if($metadata_part !== "")
					{
						// Add the letter and numbers matching the requested metadata to the metadata string.
						$metadata_string .= "{$metadata_letter}{$metadata_part}";
					}
				}
			}

			// Return the filled in metadata string.
			return $metadata_string;
		}

		//
		public function parse_request($request_uri)
		{
			// Initialize empty structure
			$request_parts = [];

			// Parse the request URI.
			@preg_match(self::REGEXP_REQUEST, $request_uri, $request_parts);
			@preg_match(self::REGEXP_PARAMETERS, $request_parts['parameters'], $request_parts['parameters']);
			@preg_match(self::REGEXP_METADATA, $request_parts['parameters']['required'], $request_parts['parameters']['required']);
			@preg_match(self::REGEXP_METADATA, $request_parts['parameters']['optional'], $request_parts['parameters']['optional']);

			return $request_parts;
		}

		//
		public function validate_request()
		{
			// Initalized an assumed successful status.
			$this->statusConfirmation =
			[
				'status' => self::STATUS_CODES['SUCCESSFUL'],
				'message' => ''
			];

			try
			{
				// Validate that the response was received as POST request.
				if(!isset($_SERVER['REQUEST_METHOD']) or $_SERVER['REQUEST_METHOD'] != 'POST')
				{
					throw new InternalException("Unsupported request method.", self::STATUS_CODES['MALFORMED_RESPONSE']);
				}

				// Attempt to decode the response data.
				$responseObject = json_decode(@file_get_contents("php://input"), true);

				// Validate that the response is JSON encoded.
				if($responseObject === null)
				{
					throw new InternalException("Response data is not a valid JSON object.", self::STATUS_CODES['MALFORMED_RESPONSE']);
				}

				// Validate if the required field 'request' exists.
				if(!isset($responseObject['request']))
				{
					throw new InternalException("Response data is missing required 'request' property.", self::STATUS_CODES['MALFORMED_RESPONSE']);
				}

				// Validate if the required field 'address' exists.
				if(!isset($responseObject['address']))
				{
					throw new InternalException("Response data is missing required 'adress' property.", self::STATUS_CODES['MALFORMED_RESPONSE']);
				}

				// Validate if the required field 'signature' exists.
				if(!isset($responseObject['signature']))
				{
					throw new InternalException("Response data is missing required 'signature' property.", self::STATUS_CODES['MALFORMED_RESPONSE']);
				}

				// Initialize empty structures
				$requestParts = [];
				$requestParameters = [];
				$requestRequired = [];
				$requestOptional = [];

				// Parse the request URI.
				$parseRequest = @preg_match(self::REGEXP_REQUEST, $responseObject['request'], $requestParts);
				$parseParameters = @preg_match(self::REGEXP_PARAMETERS, $requestParts['parameters'], $requestParameters);
				$parseRequired = @preg_match(self::REGEXP_METADATA, $requestParameters['required'], $requestRequired);
				$parseOptional = @preg_match(self::REGEXP_METADATA, $requestParameters['optional'], $requestOptional);

				// Validate overall structure.
				if($parseRequest === false)
				{
					throw new InternalException("Internal server error, could not evaluate request structure.", self::STATUS_CODES['INTERNAL_ERROR']);
				}
				else if($parseRequest == 0)
				{
					throw new InternalException("Request URI is invalid.", self::STATUS_CODES['MALFORMED_RESPONSE']);
				}

				// Validate the request scheme.
				if($requestParts['scheme'] != 'cashid:')
				{
					throw new InternalException("Request scheme '{$requestParts['scheme']}' is invalid, should be 'cashid:'.", self::STATUS_CODES['MALFORMED_RESPONSE']);
				}

				// Validate the request domain.
				if($requestParts['domain'] != $this->domain)
				{
					throw new InternalException("Request scheme '{$requestParts['domain']}' is invalid, this service is '{$this->domain}'.", self::STATUS_CODES['MALFORMED_RESPONSE']);
				}

				// Validate the parameter structure
				if($parseParameters === false)
				{
					throw new InternalException("Internal server error, could not evaluate request parameters.", self::STATUS_CODES['INTERNAL_ERROR']);
				}
				else if($parseParameters == 0)
				{
					throw new InternalException("Request parameters are invalid.", self::STATUS_CODES['MALFORMED_RESPONSE']);
				}

				// Validate the existance of a nonce.
				if(!isset($requestParameters['nonce']))
				{
					throw new InternalException("Request parameter 'nonce' is missing.", self::STATUS_CODES['MALFORMED_RESPONSE']);
				}

				// Locally store if the request action is a user-initiated action.
				$user_initiated_request = isset(self::USER_ACTIONS[$requestParameters['action']]);

				// Locally store values to compare with nonce timestamp to validate recency.
				// NOTE: current time is set to 1 minute in the future to allow for minor clock drift.
				$recent_time = (time() - (60 * 60 * 15));
				$current_time = (time() + (60 * 1 * 1));

				// Validate if a user initiated request is a recent and valid timestamp...
				if($user_initiated_request and (($requestParameters['nonce'] < $recent_time) or ($requestParameters['nonce'] > $current_time)))
				{
					throw new InternalException("Request nonce for user initated action is not a valid and recent timestamp.", self::STATUS_CODES['MALFORMED_RESPONSE']);
				}

				// Try to load the request from the apcu object cache.
				$requestReference = apcu_fetch("cashid_request_{$requestParameters['nonce']}");

				// Validate that the request was issued by this service provider.
				if(!$user_initiated_request and ($requestReference === false))
				{
					throw new InternalException("The request nonce was not issued by this service.", self::STATUS_CODES['NONCE_INVALID']);
				}

				// Validate if the request is available
				if(!$user_initiated_request and ($requestReference['available'] === false))
				{
					throw new InternalException("The request nonce was not issued by this service.", self::STATUS_CODES['NONCE_CONSUMED']);
				}

				// Validate if the request has expired.
				if(!$user_initiated_request and ($requestReference['expires'] < time()))
				{
					throw new InternalException("The request has expired and is no longer available.", self::STATUS_CODES['NONCE_EXPIRED']);
				}

				// Send the request parts to bitcoind for signature verification.
				$verificationStatus = $this->verifymessage($responseObject['address'], $responseObject['signature'], $responseObject['request']);

				// Validate the signature.
				if($verificationStatus !== true)
				{
					throw new InternalException("Signature verification failed: {$this->rpc_error}", self::STATUS_CODES['INVALID_SIGNATURE']);
				}

				// Store the response object in local cache.
				if(!apcu_store("cashid_response_{$requestParameters['nonce']}", $responseObject))
				{
					throw new InternalException("Internal server error, could not store response object.", self::STATUS_CODES['INTERNAL_ERROR']);
				}

				// Store the confirmation object in local cache.
				if(!apcu_store("cashid_confirmation_{$requestParameters['nonce']}", $this->statusConfirmation))
				{
					throw new InternalException("Internal server error, could not store confirmation object.", self::STATUS_CODES['INTERNAL_ERROR']);
				}

				// Add the action and data parameters to the response structure.
				$responseObject['action'] = (isset($parseParameters['action']) ? $parseParameters['action'] : 'auth');
				$responseObject['data'] = (isset($parseParameters['data']) ? $parseParameters['data'] : '');

				// Return the parsed response.
				return $responseObject;
			}
			catch(InternalException $exception)
			{
				// Update internal status object.
				$this->statusConfirmation =
				[
					'status' => $exception->getCode(),
					'message' => $exception->getMessage()
				];

				// Return false to indicate error.
				return false;
			}
		}

		//
		public function confirm_request()
		{
			// Sanity check if headers have already been sent.
			if(headers_sent())
			{
				throw new \Exception('cashid->confirm_request was called after data had been transmitted to the client, which prevents setting the required headers.');
			}

			// Sanity check if validation has not yet been done.
			if(!isset($this->statusConfirmation['status']))
			{
				throw new \Exception('cashid->confirm_request was called before validate_request so there is no confirmation to transmit to the client.');
			}
			
			// Configure confirmation message type.
			header('Content-type: application/json; charset=utf-8');
			header('Cache-Control: no-cache');

			// send the response confirmation back to the identity manager.
			echo json_encode($this->statusConfirmation);
		}
	}

	$cashid = new CashID();
?>
