<?php

namespace tagadvance\elephant\retry;

use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
	/**
	 * This covers #retryIfResult, #withRetryListener, #with*Strategy, and #wrap.
	 */
	function testRetryIfResult()
	{
		$expected = 'bar';

		$attempts = 3;
		$range = range(1, $attempts);

		$waitStrategy = \Mockery::mock(WaitStrategy::class)
			->shouldReceive('computeSleepTime')->andReturnValues($range)
			->getMock();

		$blockStrategy = \Mockery::mock(BlockStrategy::class)
			->shouldReceive('block')->with(1)
			->shouldReceive('block')->with(2)
			->getMock();

		$retryListener = \Mockery::mock(RetryListener::class)
			->shouldReceive('onRetry')->with(
				\Mockery::on(function (Attempt $attempt) use (&$range) {
					$expected = array_shift($range);
					$actual = $attempt->getAttemptNumber();
					$this->assertEquals($expected, $actual);

					return true;
				})
			)
			->getMock();

		$generator = (function () {
			yield 'foo';
			yield 'foo';
			yield 'bar';
		})();
		$callable = function () use ($generator) {
			try {
				return $generator->current();
			} finally {
				$generator->next();
			}
		};

		$foo = RetryerBuilder::newBuilder()
			->retryIfResult(fn($result) => $result === 'foo')
			->withStopStrategy(StopStrategies::stopAfterAttempt($attempts))
			->withWaitStrategy($waitStrategy)
			->withBlockStrategy($blockStrategy)
			->withRetryListener($retryListener)
			->build()
			->wrap($callable);

		$this->assertEquals($expected, $foo());
	}

	function testRetryIfExceptionOfType()
	{
		$exceptions = [
			new \BadFunctionCallException(),
			new \BadMethodCallException(),
			new \DomainException(),
		];
		$callable = function () use (&$exceptions) {
			throw array_shift($exceptions);
		};

		try {
			RetryerBuilder::newBuilder()
				->retryIfExceptionOfType(\BadFunctionCallException::class)
				->retryIfExceptionOfType(\BadMethodCallException::class)
				->build()
				->call($callable);
		} catch (ExecutionException $e) {
			$this->expectException(\DomainException::class);
			throw $e->getPrevious();
		}
	}

	function testRetryIfException()
	{
		$exceptions = [
			new \BadFunctionCallException(),
			new \BadMethodCallException(),
			new \DomainException(),
		];

		$exceptionPredicate = fn(\Throwable $t) => in_array(get_class($t), [
			\BadFunctionCallException::class,
			\BadMethodCallException::class
		]);

		$callable = function () use (&$exceptions) {
			throw array_shift($exceptions);
		};

		try {
			RetryerBuilder::newBuilder()
				->retryIfException($exceptionPredicate)
				->build()
				->call($callable);
		} catch (ExecutionException $e) {
			$this->expectException(\DomainException::class);
			throw $e->getPrevious();
		}
	}
}
