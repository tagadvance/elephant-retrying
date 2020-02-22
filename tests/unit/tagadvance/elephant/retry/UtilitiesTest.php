<?php

namespace tagadvance\elephant\retry;

use PHPUnit\Framework\TestCase;

class UtilitiesTest extends TestCase
{
	function testIsThrowableWithFoo()
	{
		$isThrowable = Utilities::isThrowable('Foo');

		$this->assertFalse($isThrowable);
	}

	function testIsThrowableWithInvalidClass()
	{
		$isThrowable = Utilities::isThrowable(Utilities::class);

		$this->assertFalse($isThrowable);
	}

	function testIsThrowableWithThrowable()
	{
		$isThrowable = Utilities::isThrowable(\Throwable::class);

		$this->assertTrue($isThrowable);
	}

	function testIsThrowableWithException()
	{
		$isThrowable = Utilities::isThrowable(\Exception::class);

		$this->assertTrue($isThrowable);
	}
}
