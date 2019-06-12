<?php
namespace CashID;

// Create an internal exception type which lets us catch our own exceptions 
// while still passing on system exceptions that we didn't handle.
class InternalException extends \Exception
{
}
