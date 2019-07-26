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
        $notary = new \CashID\Notary\DefaultNotary();
        $this->expectException(CashIDException::class);
        $generator = new RequestGenerator("demo.cashid.info", "/api/parse.php", $notary);
    }
}
