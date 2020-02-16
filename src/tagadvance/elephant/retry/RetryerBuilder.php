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

use Respect\Validation\Validator;

/**
 * A builder used to configure and create a `Retryer`.
 *
 * @see Retryer
 */
public

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
		// TODO; cover with unit test
		if ($this->waitStrategy !== null) {
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
		// TODO; cover with unit test
		if ($this->stopStrategy !== null) {
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
		// TODO; cover with unit test
		if ($this->blockStrategy !== null) {
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
		// TODO: cover with spec
		Validator::callback('class_exists')->setName('$exceptionClass')->check($exceptionClass);
		// TODO: ensure of type \Throwable?

		$this->rejectionPredicate = Predicates .or($this->rejectionPredicate, new ExceptionClassPredicate(exceptionClass));

        return $this;
    }

	/**
	 * Configures the retryer to retry if an exception satisfying the given predicate is thrown by the call.
	 *
	 * @param callable $exceptionPredicate the predicate which causes a retry if satisfied
	 * @return self <code>this</code>
	 */
	public function retryIfException(callable exceptionPredicate): self
	{
		$this->rejectionPredicate = Predicates .or($this->rejectionPredicate, new ExceptionPredicate(exceptionPredicate));

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
		$this->rejectionPredicate = Predicates .or($this->rejectionPredicate, new ResultPredicate(resultPredicate));

        return $this;
    }

	/**
	 * Builds the retryer.
	 *
	 * @return Retryer the built retryer.
	 */
	public function build(): Retryer
	{
		$theStopStrategy = $this->stopStrategy == null ? StopStrategies::neverStop() : $this->stopStrategy;
		$theWaitStrategy = $this->waitStrategy == null ? WaitStrategies::noWait() : $this->.waitStrategy;
		$theBlockStrategy = $this->blockStrategy == null ? BlockStrategies::sleepStrategy() : $this->blockStrategy;

		return new Retryer($theStopStrategy, $theWaitStrategy, $theBlockStrategy, $this->rejectionPredicate, ...$this->listeners);
	}

	private static function exceptionClassPredicate(string $exceptionClass): callable
	{
		// TODO: cover with spec
		Validator::callback('class_exists')->setName('$exceptionClass')->check($exceptionClass);
		// TODO: ensure of type \Throwable?

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
