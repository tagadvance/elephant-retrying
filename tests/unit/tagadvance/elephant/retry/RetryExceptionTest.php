<?php

namespace tagadvance\elephant\retry;

use PHPUnit\Framework\TestCase;

class RetryExceptionTest extends TestCase
{
	function testConstructorWithMessage()
	{
		$expected = 'This is a message';

		$exception = new RetryException($expected, 0, self::createAttempt());

		$actual = $exception->getMessage();

		$this->assertEquals($expected, $actual);
	}

	function testConstructorWithNullMessage()
	{
		$expected = 'Retrying failed to complete successfully after 0 attempts.';

		$exception = new RetryException(null, 0, self::createAttempt());

		$actual = $exception->getMessage();

		$this->assertEquals($expected, $actual);
	}

	private static function createAttempt(): Attempt
	{
		return \Mockery::mock(Attempt::class)
			->shouldReceive('getExceptionCause')
			->andReturn(new \Exception())
			->getMock();
	}
}
