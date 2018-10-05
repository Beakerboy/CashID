# CashID libraries

## PHP

### Dependencies

- PECL APCu (https://pecl.php.net/package/APCu)
- BitcoinD full node with a supported JSON RPC

### Configuration

Set the domain and path to your CashID controller that manages responses.

```PHP
// Location for a CashID response manager.
private $domain = 'demo.cashid.info';
private $path = "/api/parse.php";
```

Set the RPC username, password and location of your Bitcoin full node

```PHP
// Credentials that grant access to a bitcoind RPC connection.
private $rpc_username = 'uvzOQgLc4VujgDfVpNsfujqasVjVQHhB';
private $rpc_password = '1Znrf7KClQjJ3AhxDwr7vkFZpwW0ZGUJ';

// Location of a bitcoind RCP service.
private $rpc_scheme = 'http://';
private $rpc_hostname = 'localhost';
private $rpc_portnumber = 8332;
```

### Create CashID request

```PHP
<?php
    // Include the CashID support library for PHP.
    include('libs/cashid.php');

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
    include('libs/cashid.php');

    // Parse the request.
    $request = $cashid->validate()

    // Validate the request.
    if($request !== false)
    {
        // Perform the $request['action'] using $request['data'] and $request['metadata'].
    }
?>
```
