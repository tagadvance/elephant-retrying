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
    private static ?StopStrategy $neverStop = null;

    private function __construct() {}

    /**
     * Never gives up, so `Retryer::call()` may block forever. This is the `RetryerBuilder` default; pair it with
     * a wait strategy that backs off before pointing it at a service.
     */
    public static function neverStop(): StopStrategy
    {
        return self::$neverStop ??= new NeverStopStrategy();
    }

    /**
     * Gives up once `$attemptNumber` attempts have failed: a total call count rather than a retry count, so 3
     * means one call and two retries.
     *
     * @throws \InvalidArgumentException if `$attemptNumber` is less than 1
     */
    public static function stopAfterAttempt(int $attemptNumber): StopStrategy
    {
        return new StopAfterAttemptStrategy($attemptNumber);
    }

    /**
     * Gives up once `$delayInSeconds` has elapsed since the first attempt began. The budget is only checked
     * between attempts, so a single slow call can overrun it by any amount.
     *
     * @throws \InvalidArgumentException if `$delayInSeconds` is negative
     */
    public static function stopAfterDelay(float $delayInSeconds): StopStrategy
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
        Validator::min(0)->setName('$maxDelay')->check($maxDelay);
        $this->maxDelay = $maxDelay;
    }

    public function shouldStop(Attempt $failedAttempt): bool
    {
        return $failedAttempt->getDelaySinceFirstAttempt() >= $this->maxDelay;
    }
}
