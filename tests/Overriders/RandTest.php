<?php

namespace CashID;

class RandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @testCase testRerunDuplicateNonce
     */
    public function testRerunDuplicateNonce()
    {
        \CoreOverrider\OverriderBase::createMock("CashID", "rand");
        RandOverrider::setOverride();
        RandOverrider::setValues([100000000, 100000001]);
        $this->assertEquals(100000000, rand());
        $this->assertEquals(100000001, rand());
        RandOverrider::unsetOverride();
    }
}
