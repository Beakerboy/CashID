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
     *
     * @dataProvider dataProviderForTestCreateRequest
     */
    public function testCreateRequest($non_dependency)
    {
        // The RequestGenerator does not have a dependency for the provided item
        $this->expectException(CashIDException::class);

        // We expect it to throw an exception when constructed with an invalid dependency
        $generator = new RequestGenerator("demo.cashid.info", "/api/parse.php", $non_dependency);
    }

    public function dataProviderForTestCreateRequest()
    {
        return [
            // Object that is not a defined dependency
            [new \stdClass()],
            // Non-object
            ["Text"],
        ];
    }
}
