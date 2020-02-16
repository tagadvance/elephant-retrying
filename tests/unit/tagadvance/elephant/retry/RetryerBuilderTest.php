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

	function testWithWaitStrategyValidation()
	{
		$this->expectException(IllegalStateException::class);

		$strategy = \Mockery::mock(WaitStrategy::class);
		RetryerBuilder::newBuilder()->withWaitStrategy($strategy)->withWaitStrategy($strategy);
	}

	function testWithStopStrategyValidation()
	{
		$this->expectException(IllegalStateException::class);

		$strategy = \Mockery::mock(StopStrategy::class);
		RetryerBuilder::newBuilder()->withStopStrategy($strategy)->withStopStrategy($strategy);
	}

	function testWithBlockStrategyValidation()
	{
		$this->expectException(IllegalStateException::class);

		$strategy = \Mockery::mock(BlockStrategy::class);
		RetryerBuilder::newBuilder()->withBlockStrategy($strategy)->withBlockStrategy($strategy);
	}
}
