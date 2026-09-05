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
     * If `$lastFailedAttempt` holds an exception, it becomes this exception's `getPrevious()` cause.
     *
     * @param string|null $message `null` selects the default, "Retrying failed to complete successfully after
     * N attempts."; that is what `Retryer` itself passes
     */
    public function __construct(?string $message, int $numberOfFailedAttempts, Attempt $lastFailedAttempt)
    {
        $message = $message ?? "Retrying failed to complete successfully after $numberOfFailedAttempts attempts.";
        $code = 0;
        $cause = $lastFailedAttempt->hasException() ? $lastFailedAttempt->getExceptionCause() : null;
        parent::__construct($message, $code, $cause);

        $this->numberOfFailedAttempts = $numberOfFailedAttempts;
        $this->lastFailedAttempt = $lastFailedAttempt;
    }

    public function getNumberOfFailedAttempts(): int
    {
        return $this->numberOfFailedAttempts;
    }

    public function getLastFailedAttempt(): Attempt
    {
        return $this->lastFailedAttempt;
    }
}
