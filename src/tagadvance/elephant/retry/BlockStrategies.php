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
 * Factory class for `BlockStrategy` instances.
 *
 * @see BlockStrategy
 */
final class BlockStrategies
{
    private static ?BlockStrategy $sleepStrategy = null;

    private function __construct() {}

    /**
     * Returns the `RetryerBuilder` default: a block strategy that suspends the running process with `usleep()`.
     */
    public static function sleepStrategy(): BlockStrategy
    {
        return self::$sleepStrategy ??= new SleepStrategy();
    }
}

final class SleepStrategy implements BlockStrategy
{
    public const MICROSECONDS_PER_SECOND = 1000000.0;

    /**
     * Resolution is one microsecond, so anything shorter truncates to no wait at all.
     *
     * @throws \ValueError if `$sleepSeconds` is negative, or large enough (beyond roughly 9.2e12 seconds) that
     * the conversion to microseconds overflows and wraps negative
     */
    public function block(float $sleepSeconds): void
    {
        $microseconds = intval($sleepSeconds * self::MICROSECONDS_PER_SECOND);
        usleep($microseconds);
    }
}
