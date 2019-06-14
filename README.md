# CashID libraries
[![Build Status](https://travis-ci.org/Beakerboy/CashID.svg?branch=master)](https://travis-ci.org/Beakerboy/CashID)
[![Coverage Status](https://coveralls.io/repos/github/Beakerboy/CashID/badge.svg?branch=master)](https://coveralls.io/github/Beakerboy/CashID?branch=master)
[![Documentation](https://codedocs.xyz/Beakerboy/CashID.svg)](https://codedocs.xyz/Beakerboy/CashID/)
## PHP

### Dependencies

- PECL APCu (https://pecl.php.net/package/APCu)
- BitcoinPHP ECDSA Library

### Specifications
- CashID API (https://gitlab.com/cashid/protocol-specification)

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
