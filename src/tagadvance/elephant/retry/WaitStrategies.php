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
 * Factory class for instances of `WaitStrategy`.
 *
 * @see WaitStrategy
 */
final class WaitStrategies
{

	private function __construct()
	{
	}

	/**
	 * Returns a wait strategy that doesn't sleep at all before retrying. Use this at your own risk.
	 *
	 * @return WaitStrategy a wait strategy that doesn't wait between retries
	 */
	public static function noWait(): WaitStrategy
	{
		static $instance = null;
		if ($instance === null) {
			$instance = new FixedWaitStrategy();
		}

		return $instance;
	}

	/**
	 * Returns a wait strategy that sleeps a fixed amount of time before retrying.
	 *
	 * @param float $sleepTimeSeconds the time to sleep
	 * @return WaitStrategy a wait strategy that sleeps a fixed amount of time
	 * @throws \InvalidArgumentException if `$sleepTimeSeconds` < 0
	 */
	public static function fixedWait(float $sleepTimeSeconds): WaitStrategy
	{
		return new FixedWaitStrategy($sleepTimeSeconds);
	}

	/**
	 * Returns a strategy that sleeps a random amount of time before retrying.
	 *
	 * @param float $minimumTimeSeconds the minimum time to sleep
	 * @param float $maximumTimeSeconds the maximum time to sleep
	 * @return RandomWaitStrategy a wait strategy with a random wait time
	 * @throws \InvalidArgumentException if `$minimumTimeSeconds` < 0
	 * @throws \InvalidArgumentException if `$minimumTimeSeconds` >= `$maximumTimeSeconds`
	 */
	public static function randomWait(float $minimumTimeSeconds, float $maximumTimeSeconds): WaitStrategy
	{
		return new RandomWaitStrategy($minimumTimeSeconds, $maximumTimeSeconds);
	}

	/**
	 * Returns a strategy that sleeps a fixed amount of time after the first failed attempt and in incrementing amounts
	 * of time after each additional failed attempt.
	 *
	 * @param float $initialSleepTimeSeconds the time to sleep before retrying the first time
	 * @param float $incrementSeconds the increment added to the previous sleep time after each failed attempt
	 * @return WaitStrategy a wait strategy that incrementally sleeps an additional fixed time after each failed attempt
	 * @throws \InvalidArgumentException if `$initialSleepTimeSeconds` < 0
	 */
	public static function incrementingWait(float $initialSleepTimeSeconds, float $incrementSeconds): WaitStrategy
	{
		return new IncrementingWaitStrategy($initialSleepTimeSeconds, $incrementSeconds);
	}

	/**
	 * Returns a strategy which sleeps for an exponential amount of time after the first failed attempt, and in
	 * exponentially incrementing amounts after each failed attempt up to the maximumTime.
	 * The wait time between the retries can be controlled by the multiplier.
	 *
	 * @param float $multiplier multiply the wait time calculated by this
	 * @param float $maximumTimeSeconds the maximum time to sleep
	 * @return WaitStrategy a wait strategy that increments with each failed attempt using exponential backoff
	 * @throws \InvalidArgumentException
	 */
	public static function exponentialWait(float $multiplier = 1, float $maximumTimeSeconds = PHP_FLOAT_MAX): WaitStrategy
	{
		return new ExponentialWaitStrategy($multiplier, $maximumTimeSeconds);
	}


	/**
	 * Returns a strategy which sleeps for an increasing amount of time after the first failed attempt,
	 * and in Fibonacci increments after each failed attempt up to the `$maximumTimeSeconds`.
	 * The wait time between the retries can be controlled by the multiplier.
	 * nextWaitTime = fibonacciIncrement * `$multiplier`.
	 *
	 * @param float $multiplier multiply the wait time calculated by this
	 * @param float $maximumTimeSeconds the maximum time to sleep
	 * @return WaitStrategy a wait strategy that increments with each failed attempt using a Fibonacci sequence
	 * @throws \InvalidArgumentException
	 */
	public static function fibonacciWait(float $multiplier = 1, float $maximumTimeSeconds = PHP_FLOAT_MAX): WaitStrategy
	{
		return new FibonacciWaitStrategy($multiplier, $maximumTimeSeconds);
	}

	/**
	 * Returns a strategy which sleeps for an amount of time based on the Exception that occurred. The `$function`
	 * determines how the sleep time should be calculated for the given `exceptionClass`. If the exception does not match, a wait time of 0 is returned.
	 *
	 * @param callable $function function to calculate sleep time
	 * @param string $exceptionClass class to calculate sleep time from
	 * @return WaitStrategy a wait strategy calculated from the failed attempt
	 * @throws \InvalidArgumentException if `$exceptionClass` does not exist
	 */
	public static function exceptionWait(string $exceptionClass, callable $function): WaitStrategy
	{
		return new ExceptionWaitStrategy($exceptionClass, $function);
	}

	/**
	 * Joins one or more wait strategies to derive a composite wait strategy.
	 * The new joined strategy will have a wait time which is total of all wait times computed one after another in order.
	 *
	 * @param WaitStrategy ...$waitStrategies Wait strategies that need to be applied one after another for computing the sleep time.
	 * @return CompositeWaitStrategy a composite wait strategy
	 * @throws \InvalidArgumentException if `$waitStrategies` is empty
	 */
	public static function join(WaitStrategy...$waitStrategies): WaitStrategy
	{
		return new CompositeWaitStrategy(...$waitStrategies);
	}
}

final class FixedWaitStrategy implements WaitStrategy
{
	private float $sleepTime;

	public function __construct(float $sleepTime = 0)
	{
		Validator::min(0)->setName('$sleepTime')->check($sleepTime);

		$this->sleepTime = $sleepTime;
	}

	public function computeSleepTime(Attempt $failedAttempt): float
	{
		return $this->sleepTime;
	}
}

final class RandomWaitStrategy implements WaitStrategy
{
	private float $minimum;
	private float $maximum;

	public function __construct(float $minimum, float $maximum)
	{
		Validator::min(0)->setName('$minimum')->check($minimum);
		$template = "\$maximum must be greater than \$minimum but \$maximum is $maximum and \$minimum is $minimum";
		Validator::callback(fn() => $maximum > $minimum)->setTemplate($template)->check(null);

		$this->minimum = $minimum;
		$this->maximum = $maximum;
	}

	public function computeSleepTime(Attempt $failedAttempt): float
	{
		$difference = $this->maximum - $this->minimum;
		$random = (float)rand() / (float)getrandmax();

		return $this->minimum + ($difference * $random);
	}
}

final class IncrementingWaitStrategy implements WaitStrategy
{
	private float $initialSleepTime;
	private float $increment;

	public function __construct(float $initialSleepTime, float $increment)
	{
		Validator::min(0)->setName('$initialSleepTime')->check($initialSleepTime);

		$this->initialSleepTime = $initialSleepTime;
		$this->increment = $increment;
	}

	public function computeSleepTime(Attempt $failedAttempt): float
	{
		$result = $this->initialSleepTime + ($this->increment * ($failedAttempt->getAttemptNumber() - 1));
		return $result >= 0 ? $result : 0;
	}
}

final class ExponentialWaitStrategy implements WaitStrategy
{
	private float $multiplier;
	private float $maximumWait;

	public function __construct(float $multiplier, float $maximumWait)
	{
		Validator::min(0, false)->setName('$multiplier')->check($multiplier);
		Validator::min(0)->setName('$maximumWait')->check($maximumWait);
		$template = "\$multiplier must be less than \$maximumWait but \$multiplier is $multiplier and \$maximumWait is $maximumWait";
		Validator::callback(fn() => $multiplier < $maximumWait)->setTemplate($template)->check(null);

		$this->multiplier = $multiplier;
		$this->maximumWait = $maximumWait;
	}

	public function computeSleepTime(Attempt $failedAttempt): float
	{
		$attempt = $failedAttempt->getAttemptNumber();
		if ($attempt === 1) {
			return $this->multiplier;
		}

		$exp = pow(2, $attempt - 1);
		$result = $this->multiplier * $exp;
		// TODO: refactor to use min/max
		if ($result > $this->maximumWait) {
			$result = $this->maximumWait;
		}

		return $result >= 0 ? $result : 0;
	}
}

final class FibonacciWaitStrategy implements WaitStrategy
{
	private float $multiplier;
	private float $maximumWait;

	public function __construct(float $multiplier, float $maximumWait)
	{
		Validator::min(0, false)->setName('$multiplier')->check($multiplier);
		Validator::min(0)->setName('$maximumWait')->check($maximumWait);
		$template = "\$multiplier must be less than \$maximumWait but \$multiplier is $multiplier and \$maximumWait is $maximumWait";
		Validator::callback(fn() => $multiplier < $maximumWait)->setTemplate($template)->check(null);

		$this->multiplier = $multiplier;
		$this->maximumWait = $maximumWait;
	}

	public function computeSleepTime(Attempt $failedAttempt): float
	{
		$fib = $this->fib($failedAttempt->getAttemptNumber());

		$result = $this->multiplier * $fib;

		// TODO: refactor to use min/max
		if ($result > $this->maximumWait || $result < 0) {
			$result = $this->maximumWait;
		}

		return $result >= 0 ? $result : 0;
	}

	// TODO: generator?
	private function fib(int $n): float
	{
		if ($n === 0) {
			return 0;
		} else if ($n === 1) {
			return 1;
		};

		$prevPrev = 0;
		$prev = 1;
		$result = 0;

		for ($i = 2; $i <= $n; $i++) {
			$result = $prev + $prevPrev;
			$prevPrev = $prev;
			$prev = $result;
		}

		return $result;
	}
}

final class ExceptionWaitStrategy implements WaitStrategy
{
	private string $exceptionClass;
	private $function;

	public function __construct(string $exceptionClass, callable $function)
	{
		Validator::callback([Utilities::class, 'isThrowable'])->setName('$exceptionClass')->check($exceptionClass);

		$this->exceptionClass = $exceptionClass;
		$this->function = $function;
	}

	public function computeSleepTime(Attempt $lastAttempt): float
	{
		if ($lastAttempt->hasException()) {
			$cause = $lastAttempt->getExceptionCause();
			if ($cause instanceof $this->exceptionClass) {
				return ($this->function)($cause);
			}
		}
		return 0;
	}
}

final class CompositeWaitStrategy implements WaitStrategy
{
	private array $waitStrategies;

	public function __construct(WaitStrategy ...$waitStrategies)
	{
		Validator::notEmpty()->setName($waitStrategies)->check($waitStrategies);

		$this->waitStrategies = $waitStrategies;
	}

	public function computeSleepTime(Attempt $failedAttempt): float
	{
		$waitTime = 0;
		foreach ($this->waitStrategies as $waitStrategy) {
			$waitTime += $waitStrategy->computeSleepTime($failedAttempt);
		}

		return $waitTime;
	}
}
