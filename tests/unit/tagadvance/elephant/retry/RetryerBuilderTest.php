<?php

namespace tagadvance\elephant\retry;

use PHPUnit\Framework\TestCase;

class RetryerBuilderTest extends TestCase
{
	function testNewBuilder()
	{
		RetryerBuilder::newBuilder();

		$this->assertTrue(true);
	}

	function testWithRetryListener()
	{
		$listener = \Mockery::mock(RetryListener::class);
		RetryerBuilder::newBuilder()->withRetryListener($listener);

		$this->assertTrue(true);
	}

	function testWithWaitStrategyValidation()
	{
		$this->expectException(IllegalStateException::class);
		$this->expectExceptionMessage('a wait strategy has already been set');

		$strategy = \Mockery::mock(WaitStrategy::class);
		RetryerBuilder::newBuilder()->withWaitStrategy($strategy)->withWaitStrategy($strategy);
	}

	function testWithStopStrategyValidation()
	{
		$this->expectException(IllegalStateException::class);
		$this->expectExceptionMessage('a stop strategy has already been set');

		$strategy = \Mockery::mock(StopStrategy::class);
		RetryerBuilder::newBuilder()->withStopStrategy($strategy)->withStopStrategy($strategy);
	}

	function testWithBlockStrategyValidation()
	{
		$this->expectException(IllegalStateException::class);
		$this->expectExceptionMessage('a block strategy has already been set');

		$strategy = \Mockery::mock(BlockStrategy::class);
		RetryerBuilder::newBuilder()->withBlockStrategy($strategy)->withBlockStrategy($strategy);
	}

	function testRetryIfExceptionOfTypeValidation()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('$exceptionClass must be valid');

		RetryerBuilder::newBuilder()->retryIfExceptionOfType('Foo');
	}

	function testRetryIfExceptionOfType()
	{
		RetryerBuilder::newBuilder()->retryIfExceptionOfType(\Throwable::class);

		$this->assertTrue(true);
	}

	function testRetryIfException()
	{
		RetryerBuilder::newBuilder()->retryIfException(fn(\Throwable $t) => true);

		$this->assertTrue(true);
	}

	function testRetryIfResult()
	{
		RetryerBuilder::newBuilder()->retryIfResult(fn($result) => true);

		$this->assertTrue(true);
	}
}
