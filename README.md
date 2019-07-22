# CashID PHP library
[![Build Status](https://travis-ci.org/Beakerboy/CashID.svg?branch=master)](https://travis-ci.org/Beakerboy/CashID)
[![Coverage Status](https://coveralls.io/repos/github/Beakerboy/CashID/badge.svg?branch=master)](https://coveralls.io/github/Beakerboy/CashID?branch=master)
[![Documentation](https://codedocs.xyz/Beakerboy/CashID.svg)](https://codedocs.xyz/Beakerboy/CashID/)


## Dependencies

- PECL APCu (https://pecl.php.net/package/APCu)
- BitcoinPHP ECDSA Library
- CashaddrConverter

## Specifications
- CashID API (https://gitlab.com/cashid/protocol-specification)

## Setup

 Add the library to your `composer.json` file in your project:

```javascript
{
  "require": {
      "beakerboy/cashid-library": "0.*"
  }
}
```

Use [composer](http://getcomposer.org) to install the library:

```bash
$ php composer.phar install
```

Composer will install CashID inside your vendor folder. Then you can add the following to your
.php files to use the library with Autoloading.

```php
require_once(__DIR__ . '/vendor/autoload.php');
```

Alternatively, use composer on the command line to require and install CashID:

```
$ php composer.phar require Beakerboy/cashid:0.*
```

### Minimum Requirements
 * PHP 7

## Examples

### Create CashID request

```PHP
<?php
    // Specify your server details
    $domain = 'mydomain.com';
    $listener_script = '/api/parse.php';
    
    // Create a request generator
    $generator = new RequestGenerator($domain, $listener_script);

    // Create a minimal request
    $requestURI = $generator->createRequest();

    // Validate that the request was created
    if($requestURI !== false)
    {
        // Show a QR code / share with NFC the $requestURI
    }
```

### Validate CashID response

```PHP
<?php
    // Specify your server details
    $domain = 'mydomain.com';
    $listener_script = '/api/parse.php';

    // Create a response handler
    $handler = new ResponseHandler($domain, $listener_script);

    // Capture the response
    $response = file_get_contents('php://input');

    // Parse the request.
    $request = $handler->validateRequest($response);

    // Validate the request.
    if($request !== false)
    {
        // Perform the $request['action'] using $request['data'] and $request['metadata'].
    }

    // Send the request confirmation.
    $handler->confirm_request();
```
