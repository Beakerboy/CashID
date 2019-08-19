<?php

namespace CashID\Tests\CashID;

use CashID\Exceptions\CashIDException;
use CashID\Services\RequestGenerator;
use Paillechat\ApcuSimpleCache\ApcuCache;

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
        $cache = new ApcuCache();

        // The RequestGenerator does not have a dependency for the provided item
        $this->expectException(CashIDException::class);

        // We expect it to throw an exception when constructed with an invalid dependency
        $generator = new RequestGenerator("demo.cashid.info", "/api/parse.php", $cache, $non_dependency);
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
