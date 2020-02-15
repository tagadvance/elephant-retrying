<?php

namespace tagadvance\elephant\retry;

use PHPUnit\Framework\TestCase;

class RetryExceptionTest extends TestCase
{
	function testConstructorWithNullMessage()
	{
		$attempt = \Mockery::mock(Attempt::class)
			->shouldReceive('getExceptionCause')
			->andReturn(new \Exception())
			->getMock();
		$exception = new RetryException(null, 0, $attempt);

		$expected = 'Retrying failed to complete successfully after 0 attempts.';
		$actual = $exception->getMessage();
		$this->assertEquals($expected, $actual);
	}
}
