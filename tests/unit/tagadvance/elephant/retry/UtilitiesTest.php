<?php

namespace tagadvance\elephant\retry;

use PHPUnit\Framework\TestCase;

class UtilitiesTest extends TestCase
{
    public function testIsThrowableWithFoo()
    {
        $isThrowable = Utilities::isThrowable('Foo');

        $this->assertFalse($isThrowable);
    }

    public function testIsThrowableWithInvalidClass()
    {
        $isThrowable = Utilities::isThrowable(Utilities::class);

        $this->assertFalse($isThrowable);
    }

    public function testIsThrowableWithThrowable()
    {
        $isThrowable = Utilities::isThrowable(\Throwable::class);

        $this->assertTrue($isThrowable);
    }

    public function testIsThrowableWithException()
    {
        $isThrowable = Utilities::isThrowable(\Exception::class);

        $this->assertTrue($isThrowable);
    }
}
