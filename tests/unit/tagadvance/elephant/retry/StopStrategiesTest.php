<?php

namespace tagadvance\elephant\retry;

use PHPUnit\Framework\TestCase;

class StopStrategiesTest extends TestCase
{
	function testNeverStop()
	{
		$strategy = StopStrategies::neverStop();
		$attempt = \Mockery::mock(Attempt::class);

		$shouldStop = $strategy->shouldStop($attempt);
		$this->assertFalse($shouldStop);
	}

	function testStopAfterAttemptValidation()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('$maxAttemptNumber must be greater than or equal to 1');

		StopStrategies::stopAfterAttempt(0);
	}

	function testStopAfterAttempt()
	{
		$maxAttempts = 3;

		$strategy = StopStrategies::stopAfterAttempt(3);
		$attempt = \Mockery::mock(Attempt::class)
			->shouldReceive('getAttemptNumber')->andReturnValues([1, 2, 3])
			->getMock();

		for ($i = 1; $i <= $maxAttempts; $i++) {
			$shouldStop = $strategy->shouldStop($attempt);
			$method = $i >= $maxAttempts ? 'assertTrue' : 'assertFalse';
			$this->$method($shouldStop);
		}
	}

	function testStopAfterDelayValidation()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('$maxDelay must be greater than or equal to 0');

		StopStrategies::stopAfterDelay(-1);
	}

	function testStopAfterDelayZero()
	{
		$strategy = StopStrategies::stopAfterDelay(0);
		$attempt = \Mockery::mock(Attempt::class)
			->shouldReceive('getDelaySinceFirstAttempt')->andReturn(0)
			->getMock();

		$shouldStop = $strategy->shouldStop($attempt);
		$this->assertTrue($shouldStop);
	}

	function testStopAfterDelayOne()
	{
		$strategy = StopStrategies::stopAfterDelay(1);
		$attempt = \Mockery::mock(Attempt::class)
			->shouldReceive('getDelaySinceFirstAttempt')->andReturnValues([0, 1])
			->getMock();

		$shouldStop = $strategy->shouldStop($attempt);
		$this->assertFalse($shouldStop);

		$shouldStop = $strategy->shouldStop($attempt);
		$this->assertTrue($shouldStop);
	}
}
