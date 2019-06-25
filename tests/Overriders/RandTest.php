<?php

namespace CashID;

class RandTest extends \PHPUnit\Framework\TestCase
{
    use \phpmock\phpunit\PHPMock;

    /**
     * @testCase testRerunDuplicateNonce
     */
    public function testRerunDuplicateNonce()
    {
        \phpmock\phpunit\PHPMock::defineFunctionMock();
        $rand = $this->getFunctionMock(__NAMESPACE__, "rand");
        $rand->expects($this->twice())->willReturn(100000000, 100000001);
        $this->assertEquals(100000000, rand());
        $this->assertEquals(100000001, rand());
    }
}
