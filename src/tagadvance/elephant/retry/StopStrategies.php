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
 * Factory class for `StopStrategy` instances.
 *
 * @see StopStrategy
 */
final class StopStrategies
{
	private function __construct()
	{
	}

	/**
	 * Returns a stop strategy which never stops retrying. It might be best to try not to abuse services with this kind
	 * of behavior when small wait intervals between retry attempts are being used.
	 *
	 * @return NeverStopStrategy a stop strategy which never stops
	 */
	public static function neverStop(): NeverStopStrategy
	{
		static $neverStop = null;
		if ($neverStop === null) {
			$neverStop = new NeverStopStrategy();
		}

		return $neverStop;
	}

	/**
	 * Returns a stop strategy which stops after N failed attempts.
	 *
	 * @param int $attemptNumber the number of failed attempts before stopping
	 * @return StopAfterAttemptStrategy a stop strategy which stops after `$attemptNumber` attempts
	 */
	public static function stopAfterAttempt(int $attemptNumber): StopAfterAttemptStrategy
	{
		return new StopAfterAttemptStrategy($attemptNumber);
	}

	/**
	 * Returns a stop strategy which stops after a given delay. If an unsuccessful attempt is made, this `StopStrategy`
	 * will check if the amount of time that's passed from the first attempt has exceeded the given delay amount. If it
	 * has exceeded this delay, then using this strategy causes the retrying to stop.
	 *
	 * @param float $delayInSeconds the delay, starting from first attempt
	 * @return StopAfterDelayStrategy a stop strategy which stops after `$delayInSeconds`
	 */
	public static function stopAfterDelay(float $delayInSeconds): StopAfterDelayStrategy
	{
		return new StopAfterDelayStrategy($delayInSeconds);
	}
}

final class NeverStopStrategy implements StopStrategy
{
	public function shouldStop(Attempt $failedAttempt): bool
	{
		return false;
	}
}

final class StopAfterAttemptStrategy implements StopStrategy
{
	private int $maxAttemptNumber;

	public function __construct(int $maxAttemptNumber)
	{
		Validator::min(1)->setName('$maxAttemptNumber')->check($maxAttemptNumber);
		$this->maxAttemptNumber = $maxAttemptNumber;
	}

	public function shouldStop(Attempt $failedAttempt): bool
	{
		return $failedAttempt->getAttemptNumber() >= $this->maxAttemptNumber;
	}
}

final class StopAfterDelayStrategy implements StopStrategy
{
	private float $maxDelay;

	public function __construct(float $maxDelay)
	{
		Validator::min(0)->setName('maxDelay')->check($maxDelay);
		$this->maxDelay = $maxDelay;
	}

	public function shouldStop(Attempt $failedAttempt): bool
	{
		return $failedAttempt->getDelaySinceFirstAttempt() >= $this->maxDelay;
	}
}
