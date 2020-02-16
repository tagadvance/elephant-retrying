<?php

/*
 * Copyright 2012-2015 Ray Holder
 * Copyright 2020 Tag Spilman
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace tagadvance\elephant\retry;

/**
 * A retryer, which executes a call, and retries it until it succeeds, or a stop strategy decides to stop retrying. A
 * wait strategy is used to sleep between attempts. The strategy to decide if the call succeeds or not is also configurable.
 * <p></p>
 * A retryer can also wrap the callable into a RetryerCallable, which can be submitted to an executor.
 * <p></p>
 * Retryer instances are better constructed with a `RetryerBuilder`.
 *
 * @see RetryerBuilder
 */
final class Retryer
{
	private StopStrategy $stopStrategy;
	private WaitStrategy $waitStrategy;
	private BlockStrategy $blockStrategy;
	private $rejectionPredicate;
	private array $listeners;

	/**
	 * Constructor
	 *
	 * @param StopStrategy $stopStrategy the strategy used to decide when the retryer must stop retrying
	 * @param WaitStrategy $waitStrategy the strategy used to decide how much time to sleep between attempts
	 * @param BlockStrategy $blockStrategy the strategy used to decide how to block between retry attempts
	 * @param callable $rejectionPredicate the predicate used to decide if the attempt must be rejected or not. If an attempt is
	 * rejected, the retryer will retry the call, unless the stop strategy indicates otherwise or the thread is
	 * interrupted.
	 * @param RetryListener ...$listeners collection of retry listeners
	 */
	public function __construct(
		StopStrategy $stopStrategy,
		WaitStrategy $waitStrategy,
		BlockStrategy $blockStrategy,
		callable $rejectionPredicate,
		RetryListener ...$listeners
	) {
		$this->stopStrategy = $stopStrategy;
		$this->waitStrategy = $waitStrategy;
		$this->blockStrategy = $blockStrategy;
		$this->rejectionPredicate = $rejectionPredicate;
		$this->listeners = $listeners;
	}

	/**
	 * Executes the given callable. If the rejection predicate accepts the attempt, the stop strategy is used to decide
	 * if a new attempt must be made. Then the wait strategy is used to decide how much time to sleep and a new attempt
	 * is made.
	 *
	 * @param callable the callable task to be executed
	 * @return mixed the computed result of the given callable
	 * @throws ExecutionException if the given callable throws an exception, and the rejection predicate considers the
	 * attempt as successful. The original exception is wrapped into an ExecutionException.
	 * @throws RetryException if all the attempts failed before the stop strategy decided to abort, or the thread was
	 * interrupted. Note that if the thread is interrupted, this exception is thrown and the thread's interrupt status
	 * is set.
	 */
	public function call(callable $callable)
	{
		$startTime = microtime(true);

		for ($attemptNumber = 1; ; $attemptNumber++) {
			try {
				$result = attemptTimeLimiter . call($callable);
				$attempt = new ResultAttempt($result, $attemptNumber, microtime(true) - $startTime);
			} catch (\Throwable $t) {
				$attempt = new ExceptionAttempt($t, $attemptNumber, microtime(true) - $startTime);
			}

			foreach ($this->listeners as $listener) {
				$listener->onRetry($attempt);
			}

			if (!($this->rejectionPredicate)($attempt)) {
				return $attempt->get();
			}

			if ($this->stopStrategy->shouldStop($attempt)) {
				throw new RetryException($attemptNumber, $attempt);
			}

			$sleepTime = $this->waitStrategy->computeSleepTime($attempt);
			$this->blockStrategy->block($sleepTime);
		}
	}

	/**
	 * TODO: documentation
	 *
	 * @param callable the callable to wrap
	 * @return callable a callable that behaves like the given `$callable` with retry behavior defined by this `Retryer`
	 */
	public function wrap(callable $callable): callable
	{
		return fn() => $this->call($callable);
	}
}

class ResultAttempt implements Attempt
{

	private $result;
	private int $attemptNumber;
	private float $delaySinceFirstAttempt;

	public function __construct($result, int $attemptNumber, float $delaySinceFirstAttempt)
	{
		$this->result = $result;
		$this->attemptNumber = $attemptNumber;
		$this->delaySinceFirstAttempt = $delaySinceFirstAttempt;
	}

	public function get()
	{
		return $this->result;
	}

	public function hasResult(): bool
	{
		return true;
	}

	public function hasException(): bool
	{
		return false;
	}

	public function getResult()
	{
		return $this->result;
	}

	public function getExceptionCause(): \Throwable
	{
		throw new IllegalStateException('The attempt resulted in a result, not in an exception');
	}

	public function getAttemptNumber(): int
	{
		return $this->attemptNumber;
	}

	public function getDelaySinceFirstAttempt(): float
	{
		return $this->delaySinceFirstAttempt;
	}
}

final class ExceptionAttempt implements Attempt
{
	private ExecutionException $e;
	private int $attemptNumber;
	private float $delaySinceFirstAttempt;

	public function __construct(\Throwable $cause, int $attemptNumber, float $delaySinceFirstAttempt)
	{
		$this->e = new ExecutionException($cause);
		$this->attemptNumber = $attemptNumber;
		$this->delaySinceFirstAttempt = $delaySinceFirstAttempt;
	}

	public function get()
	{
		throw $this->e;
	}

	public function hasResult(): bool
	{
		return false;
	}

	public function hasException(): bool
	{
		return true;
	}

	public function getResult()
	{
		throw new IllegalStateException('The attempt resulted in an exception, not in a result');
	}

	public function getExceptionCause(): \Throwable
	{
		return $this->e->getPrevious();
	}

	public function getAttemptNumber(): int
	{
		return $this->attemptNumber;
	}

	public function getDelaySinceFirstAttempt(): float
	{
		return $this->delaySinceFirstAttempt;
	}
}
