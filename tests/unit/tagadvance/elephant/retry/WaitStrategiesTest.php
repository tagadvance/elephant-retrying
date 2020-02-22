<?php

namespace tagadvance\elephant\retry;

use PHPUnit\Framework\TestCase;

class WaitStrategiesTest extends TestCase
{
	function testNoWait()
	{
		$expected = 0;

		$attempt = \Mockery::mock(Attempt::class);

		$waitStrategy = WaitStrategies::noWait();

		$arbitrarilyLargeNumber = 100;
		for ($i = 0; $i < $arbitrarilyLargeNumber; $i++) {
			$sleepTime = $waitStrategy->computeSleepTime($attempt);
			$this->assertEquals($expected, $sleepTime);
		}
	}

	function testFixedWaitMinimumAtLeastZero()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('$sleepTime must be greater than or equal to 0');

		WaitStrategies::fixedWait(-1);
	}

	function testFixedWait()
	{
		$expected = 1;

		$attempt = \Mockery::mock(Attempt::class);

		$waitStrategy = WaitStrategies::fixedWait(1);

		$arbitrarilyLargeNumber = 100;
		for ($i = 0; $i < $arbitrarilyLargeNumber; $i++) {
			$sleepTime = $waitStrategy->computeSleepTime($attempt);
			$this->assertEquals($expected, $sleepTime);
		}
	}

	function testRandomWaitMaximumGreaterThanMinimum()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('$maximum must be greater than $minimum but $maximum is 1 and $minimum is 1');

		WaitStrategies::randomWait(1, 1);
	}

	function testRandomWaitMinimumAtLeastZero()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('$minimum must be greater than or equal to 0');

		WaitStrategies::randomWait(-1, 1);
	}

	function testRandomWait()
	{
		$attempt = \Mockery::mock(Attempt::class);

		$minimum = 0;
		$maximum = 1;
		$waitStrategy = WaitStrategies::randomWait($minimum, $maximum);

		$set = [];
		for ($i = 0; $i < 1000; $i++) {
			$sleepTime = $waitStrategy->computeSleepTime($attempt);
			$key = strval($sleepTime);

			$this->assertArrayNotHasKey($key, $set);
			$set[$key] = $sleepTime;

			$this->assertGreaterThanOrEqual($minimum, $sleepTime);
			$this->assertLessThanOrEqual($maximum, $sleepTime);
		}
	}

	function testIncrementingWaitValidation()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('$initialSleepTime must be greater than or equal to 0');

		WaitStrategies::incrementingWait(-1, 0);
	}

	function testIncrementingWait()
	{
		$attempt = \Mockery::mock(Attempt::class)
			->shouldReceive('getAttemptNumber')
			->andReturnValues([1, 2, 3, 4, 5])
			->getMock();

		$waitStrategy = WaitStrategies::incrementingWait(1, 2);

		$series = [1, 3, 5, 7, 9];
		while (!empty($series)) {
			$expected = array_shift($series);

			$sleepTime = $waitStrategy->computeSleepTime($attempt);
			$this->assertEquals($expected, $sleepTime);
		}
	}

	function testExponentialValidationMultiplierLessThanMaximumWait()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('$multiplier must be less than $maximumWait but $multiplier is 1 and $maximumWait is 1');

		WaitStrategies::exponentialWait(1, 1);
	}

	function testExponentialValidationMaximumWaitLessThanZero()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('$maximumWait must be greater than or equal to 0');

		WaitStrategies::exponentialWait(1, -1);
	}

	function testExponentialValidationMultiplierZero()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('$multiplier must be greater than 0');

		WaitStrategies::exponentialWait(0);
	}

	function testExponentialWait()
	{
		$attempt = \Mockery::mock(Attempt::class)
			->shouldReceive('getAttemptNumber')
			->andReturnValues([1, 2, 3, 4, 5])
			->getMock();

		$waitStrategy = WaitStrategies::exponentialWait();

		$series = [1, 2, 4, 8, 16];
		while (!empty($series)) {
			$expected = array_shift($series);

			$sleepTime = $waitStrategy->computeSleepTime($attempt);
			$this->assertEquals($expected, $sleepTime);
		}
	}

	function testFibonacciValidationMultiplierLessThanMaximumWait()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('$multiplier must be less than $maximumWait but $multiplier is 1 and $maximumWait is 1');

		WaitStrategies::fibonacciWait(1, 1);
	}

	function testFibonacciValidationMaximumWaitLessThanZero()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('$maximumWait must be greater than or equal to 0');

		WaitStrategies::fibonacciWait(1, -1);
	}

	function testFibonacciValidationMultiplierZero()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('$multiplier must be greater than 0');

		WaitStrategies::fibonacciWait(0);
	}

	function testFibonacciWait()
	{
		$attempt = \Mockery::mock(Attempt::class)
			->shouldReceive('getAttemptNumber')
			->andReturnValues([1, 2, 3, 4, 5])
			->getMock();

		$waitStrategy = WaitStrategies::fibonacciWait();

		$fibonacci = [1, 1, 2, 3, 5];
		while (!empty($fibonacci)) {
			$expected = array_shift($fibonacci);

			$sleepTime = $waitStrategy->computeSleepTime($attempt);
			$this->assertEquals($expected, $sleepTime);
		}
	}

	function testExceptionValidation()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('$exceptionClass must be valid');

		$exceptionHandler = fn() => 0;
		WaitStrategies::exceptionWait('Foo', $exceptionHandler);
	}

	function testExceptionWait()
	{
		$exceptionHandler = fn(\RuntimeException $e) => 1;

		$attempt = \Mockery::mock(Attempt::class)
			->shouldReceive('hasException')
			->andReturn(true)
			->shouldReceive('getExceptionCause')
			->andReturn(new \RuntimeException())
			->getMock();

		$waitStrategy = WaitStrategies::exceptionWait(\RuntimeException::class, $exceptionHandler);

		$sleepTime = $waitStrategy->computeSleepTime($attempt);
		$this->assertEquals(1, $sleepTime);
	}

	function testExceptionWaitZero()
	{
		$exceptionHandler = fn(\RuntimeException $e) => 1;

		$attempt = \Mockery::mock(Attempt::class)
			->shouldReceive('hasException')
			->andReturn(true)
			->shouldReceive('getExceptionCause')
			->andReturn(new \Exception())
			->getMock();

		$waitStrategy = WaitStrategies::exceptionWait(\RuntimeException::class, $exceptionHandler);

		$sleepTime = $waitStrategy->computeSleepTime($attempt);
		$this->assertEquals(0, $sleepTime);
	}

	function testJoinValidation()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('{ } must not be empty');

		WaitStrategies::join();
	}

	function testJoin()
	{
		$expected = 3;

		$waitStrategy1 = \Mockery::mock(WaitStrategy::class)
			->shouldReceive('computeSleepTime')
			->andReturn(1)
			->getMock();

		$waitStrategy2 = \Mockery::mock(WaitStrategy::class)
			->shouldReceive('computeSleepTime')
			->andReturn(2)
			->getMock();

		$attempt = \Mockery::mock(Attempt::class);

		$waitStrategy = WaitStrategies::join($waitStrategy1, $waitStrategy2);

		$sleepTime = $waitStrategy->computeSleepTime($attempt);
		$this->assertEquals($expected, $sleepTime);
	}
}
