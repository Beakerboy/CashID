<?php

namespace CashID\Tests\CashID;

use CashID\Exceptions\CashIDException;
use CashID\Services\RequestGenerator;

/**
 * Test the CashIDService class
 *
 * Unit tests for each function
 */
class CashIDServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test that setDependencies throws an exception
     */
    public function testCreateRequest($action, $data, $metadata, $expected)
    {
        // Create a DefaultNotary
        $notary = new \CashID\Notary\DefaultNotary();

        // The RequestGenerator does not have a dependency for the NotaryInterface
        $this->expectException(CashIDException::class);

        // We expect it to throw an exception when constructed with an invalid dependency
        $generator = new RequestGenerator("demo.cashid.info", "/api/parse.php", $notary);
    }
}
