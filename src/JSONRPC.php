<?php
//
class JSONRPC
{
    // Request counter
    private static $rpc_request_id = 1;
    // Storage for errors caused by RPC calls.
    private static $rpc_error = null;
    // Convert missing functions to RPC calls.
    public function __call($function, $arguments)
    {
        // Remove array keys to simplify argument list?
        $rpc_arguments = array_values($arguments);
        // Convert underscores to spaces, and force lowercase in function names.
        $rpc_function = str_replace('_', '', strtolower($function));
        // Form the RCP URL to which we can send the request.
        $rpc_url = RPC_SCHEME . RPC_USERNAME . ":" . RPC_PASSWORD . "@" . RPC_HOST . ":" . RPC_PORT;
        // Set up an RPC request.
        $rpc_request = [
            'jsonrpc' => '2.0',
            'id' => self::$rpc_request_id++,
            'method' => $rpc_function,
            'params' => $rpc_arguments,
        ];
        // Set up a stream request.
        $stream_request = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/json',
                'content' => json_encode($rpc_request),
                'ignore_errors' => true,
            ],
        ];
        // Create a connection with the RCP host.
        $stream_context = stream_context_create($stream_request);
        // Send the request and store the full response.
        $rpc_response = file_get_contents($rpc_url, false, $stream_context);
        // Validate if the request was completed
        if($rpc_response === false) {
            // Store error description
            self::$rpc_error = 'Unable to complete RPC request.';
            // Return NULL to indicate that an error was encountered.
            return NULL;
        } else {
            // Attempt to decode the RCP response
            $object = @json_decode($rpc_response, true);
            // Validate if the RPC response is a valid JSON string.
            if($object === NULL) {
                // Store error description
                self::$rpc_error = 'Reply from RPC host was not a valid JSON string.';
                // Return NULL to indicate that an error was encountered.
                return NULL;
            } else {
                // Check if the response contains any errors.
                if($object['error']) {
                    // Store the response error message.
                    self::$rpc_error = $object['error']['message'];
                    // Return NULL to indicate that an error was encountered.
                    return NULL;
                } else {
                    // Clear any previous error descriptions.
                    self::$rpc_error = '';
                    // Return the RPC response data.
                    return $object['result'];
                }
            }
        }
    }
}
