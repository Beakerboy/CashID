# CashID libraries
[![Build Status](https://travis-ci.org/Beakerboy/CashID.svg?branch=master)](https://travis-ci.org/Beakerboy/CashID)
[![Coverage Status](https://coveralls.io/repos/github/Beakerboy/CashID/badge.svg?branch=master)](https://coveralls.io/github/Beakerboy/CashID?branch=master)
[![Documentation](https://codedocs.xyz/Beakerboy/CashID.svg)](https://codedocs.xyz/Beakerboy/CashID/)
## PHP

### Dependencies

- PECL APCu (https://pecl.php.net/package/APCu)
- BitcoinD full node with a supported JSON RPC

### Configuration

Edit the cashid.php file and set the domain and path to your CashID controller that manages responses.

```PHP
// Location pointing to a CashID response manager.
const SERVICE_DOMAIN = 'demo.cashid.info';
const SERVICE_PATH = "/api/parse.php";
```

Set the RPC username, password and location of your Bitcoin full node

```PHP
// Credentials that grant access to a bitcoind RPC connection.
const RPC_USERNAME = 'uvzOQgLc4VujgDfVpNsfujqasVjVQHhB';
const RPC_PASSWORD = '1Znrf7KClQjJ3AhxDwr7vkFZpwW0ZGUJ';

// Location of a bitcoind RCP service.
const RPC_SCHEME = 'http://';
const RPC_HOST = '127.0.0.1';
const RPC_PORT = 8332;
```

### Create CashID request

```PHP
<?php
    // Include the CashID support library for PHP.
    require_once('lib/cashid.php');

    // Create a minimal request
    $requestURI = $cashid->create_request();

    // Validate that the request was created
    if($requestURI !== false)
    {
        // Show a QR code / share with NFC the $requestURI
    }
?>
```

### Validate CashID request

```PHP
<?php
    // Include the CashID support library for PHP.
    require_once('lib/cashid.php');

    // Parse the request.
    $request = $cashid->validate_request()

    // Validate the request.
    if($request !== false)
    {
        // Perform the $request['action'] using $request['data'] and $request['metadata'].
    }

    // Send the request confirmation.
    $cashid->confirm_request();
?>
```
