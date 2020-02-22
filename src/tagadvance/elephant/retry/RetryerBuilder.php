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

declare(strict_types=1);

namespace tagadvance\elephant\retry;

use Respect\Validation\Validator;

/**
 * A builder used to configure and create a `Retryer`.
 *
 * @see Retryer
 */
class RetryerBuilder
{
	private StopStrategy $stopStrategy;
	private WaitStrategy $waitStrategy;
	private BlockStrategy $blockStrategy;
	private $rejectionPredicate;
	private array $listeners;

	private function __construct()
	{
		$this->rejectionPredicate = fn() => false;
		$this->listeners = [];
	}

	/**
	 * Constructs a new builder
	 *
	 * @return self the new builder
	 */
	public static function newBuilder(): self
	{
		return new self();
	}

	/**
	 * Adds a listener that will be notified of each attempt that is made
	 *
	 * @param RetryListener listener Listener to add
	 * @return self <code>this</code>
	 */
	public function withRetryListener(RetryListener $listener): self
	{
		$this->listeners[] = $listener;

		return $this;
	}

	/**
	 * Sets the wait strategy used to decide how long to sleep between failed attempts.
	 * The default strategy is to retry immediately after a failed attempt.
	 *
	 * @param WaitStrategy $waitStrategy the strategy used to sleep between failed attempts
	 * @return self <code>this</code>
	 * @throws IllegalStateException if a wait strategy has already been set.
	 */
	public function withWaitStrategy(WaitStrategy $waitStrategy): self
	{
		if (isset($this->waitStrategy)) {
			throw new IllegalStateException('a wait strategy has already been set');
		}

		$this->waitStrategy = $waitStrategy;

		return $this;
	}

	/**
	 * Sets the stop strategy used to decide when to stop retrying. The default strategy is to not stop at all .
	 *
	 * @param StopStrategy $stopStrategy the strategy used to decide when to stop retrying
	 * @return self <code>this</code>
	 * @throws IllegalStateException if a stop strategy has already been set.
	 */
	public function withStopStrategy(StopStrategy $stopStrategy): self
	{
		if (isset($this->stopStrategy)) {
			throw new IllegalStateException('a stop strategy has already been set');
		}

		$this->stopStrategy = $stopStrategy;

		return $this;
	}


	/**
	 * Sets the block strategy used to decide how to block between retry attempts. The default strategy is to use Thread#sleep().
	 *
	 * @param BlockStrategy $blockStrategy the strategy used to decide how to block between retry attempts
	 * @return self <code>this</code>
	 * @throws IllegalStateException if a block strategy has already been set.
	 */
	public function withBlockStrategy(BlockStrategy $blockStrategy): self
	{
		if (isset($this->blockStrategy)) {
			throw new IllegalStateException('a block strategy has already been set');
		}

		$this->blockStrategy = $blockStrategy;

		return $this;
	}

	/**
	 * Configures the retryer to retry if an exception of the given class (or subclass of the given class) is thrown by
	 * the call.
	 *
	 * @param string exceptionClass the type of the exception which should cause the retryer to retry
	 * @return self <code>this</code>
	 */
	public function retryIfExceptionOfType(string $exceptionClass): self
	{
		$this->rejectionPredicate = self::orPredicate($this->rejectionPredicate, self::exceptionClassPredicate($exceptionClass));

		return $this;
	}

	/**
	 * Configures the retryer to retry if an exception satisfying the given predicate is thrown by the call.
	 *
	 * @param callable $exceptionPredicate the predicate which causes a retry if satisfied
	 * @return self <code>this</code>
	 */
	public function retryIfException(callable $exceptionPredicate): self
	{
		$this->rejectionPredicate = self::orPredicate($this->rejectionPredicate, self::exceptionPredicate($exceptionPredicate));

		return $this;
	}

	/**
	 * Configures the retryer to retry if the result satisfies the given predicate.
	 *
	 * @param callable $resultPredicate a predicate applied to the result, and which causes the retryer to retry if the predicate
	 * is satisfied
	 * @return self <code>this</code>
	 */
	public function retryIfResult(callable $resultPredicate): self
	{
		$this->rejectionPredicate = self::orPredicate($this->rejectionPredicate, self::resultPredicate($resultPredicate));

		return $this;
	}

	/**
	 * Builds the retryer.
	 *
	 * @return Retryer the built retryer.
	 */
	public function build(): Retryer
	{
		$theStopStrategy = isset($this->stopStrategy) ? $this->stopStrategy : StopStrategies::neverStop();
		$theWaitStrategy = isset($this->waitStrategy) ? $this->waitStrategy : WaitStrategies::noWait();
		$theBlockStrategy = isset($this->blockStrategy) ? $this->blockStrategy : BlockStrategies::sleepStrategy();

		return new Retryer($theStopStrategy, $theWaitStrategy, $theBlockStrategy, $this->rejectionPredicate, ...$this->listeners);
	}

	private static function orPredicate(callable ...$callables): callable
	{
		return function (...$args) use ($callables) {
			foreach ($callables as $callable) {
				if ($callable(...$args)) {
					return true;
				}
			}

			return false;
		};
	}

	private static function exceptionClassPredicate(string $exceptionClass): callable
	{
		Validator::callback([Utilities::class, 'isThrowable'])->setName('$exceptionClass')->check($exceptionClass);

		return function (Attempt $attempt) use ($exceptionClass) {
			if (!$attempt->hasException()) {
				return false;
			}

			return $attempt->getExceptionCause() instanceof $exceptionClass;
		};
	}

	private static function resultPredicate(callable $delegate): callable
	{
		return function (Attempt $attempt) use ($delegate) {
			if (!$attempt->hasResult()) {
				return false;
			}

			$result = $attempt->getResult();
			return $delegate($result);
		};
	}

	private static function exceptionPredicate(callable $delegate): callable
	{
		return function (Attempt $attempt) use ($delegate) {
			if (!$attempt->hasException()) {
				return false;
			}

			$cause = $attempt->getExceptionCause();
			return $delegate($cause);
		};
	}

}
