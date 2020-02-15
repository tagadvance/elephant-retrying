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
 * An exception indicating that none of the attempts of the `Retryer` succeeded. If the last `Attempt` resulted in an
 * Exception, it is set as the cause of the `RetryException`.
 *
 * @see Retryer
 * @see Attempt
 */
final class RetryException extends \Exception
{

	private int $numberOfFailedAttempts;
	private Attempt $lastFailedAttempt;

	/**
	 * If the last `Attempt` had an Exception, ensure it is available in the stack trace.
	 *
	 * @param string $message Exception description to be added to the stack trace
	 * @param int $numberOfFailedAttempts times we've tried and failed
	 * @param Attempt $lastFailedAttempt what happened the last time we failed
	 */
	public function __construct(?string $message, int $numberOfFailedAttempts, Attempt $lastFailedAttempt)
	{
		$message = $message ?? "Retrying failed to complete successfully after $numberOfFailedAttempts attempts.";
		$code = 0;
		parent::__construct($message, $code, $lastFailedAttempt->getExceptionCause());

		$this->numberOfFailedAttempts = $numberOfFailedAttempts;
		$this->lastFailedAttempt = $lastFailedAttempt;
	}

	/**
	 * Returns the number of failed attempts
	 *
	 * @return int the number of failed attempts
	 */
	public function getNumberOfFailedAttempts(): int
	{
		return $this->numberOfFailedAttempts;
	}

	/**
	 * Returns the last failed attempt
	 *
	 * @return Attempt the last failed attempt
	 */
	public function getLastFailedAttempt(): Attempt
	{
		return $this->lastFailedAttempt;
	}
}
