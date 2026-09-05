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
    private function __construct() {}

    /**
     * Returns the `RetryerBuilder` default: no wait at all between attempts. Use this at your own risk.
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
     * Waits the same amount before every retry.
     *
     * @throws \InvalidArgumentException if `$sleepTimeSeconds` is negative
     */
    public static function fixedWait(float $sleepTimeSeconds): WaitStrategy
    {
        return new FixedWaitStrategy($sleepTimeSeconds);
    }

    /**
     * Draws a fresh wait for every retry from the closed interval `[$minimumTimeSeconds, $maximumTimeSeconds]`;
     * unlike upstream guava-retrying, the maximum itself can come up.
     *
     * @throws \InvalidArgumentException if `$minimumTimeSeconds` is negative, or is not less than
     * `$maximumTimeSeconds`
     */
    public static function randomWait(float $minimumTimeSeconds, float $maximumTimeSeconds): WaitStrategy
    {
        return new RandomWaitStrategy($minimumTimeSeconds, $maximumTimeSeconds);
    }

    /**
     * Waits `$initialSleepTimeSeconds` after the first failure and one further `$incrementSeconds` after each
     * failure thereafter. A negative increment is permitted and simply floors the wait at zero.
     *
     * @throws \InvalidArgumentException if `$initialSleepTimeSeconds` is negative
     */
    public static function incrementingWait(float $initialSleepTimeSeconds, float $incrementSeconds): WaitStrategy
    {
        return new IncrementingWaitStrategy($initialSleepTimeSeconds, $incrementSeconds);
    }

    /**
     * Waits `$multiplier` after the first failure and doubles it after each one thereafter, capped at
     * `$maximumTimeSeconds`. This port deliberately halves upstream guava-retrying's schedule, so it disagrees
     * with the faithful `fibonacciWait()` about what attempt 1 is worth.
     *
     * @throws \InvalidArgumentException if `$multiplier` is not greater than 0, if `$maximumTimeSeconds` is
     * negative, or if `$multiplier` is not less than `$maximumTimeSeconds`
     */
    public static function exponentialWait(float $multiplier = 1, float $maximumTimeSeconds = PHP_FLOAT_MAX): WaitStrategy
    {
        return new ExponentialWaitStrategy($multiplier, $maximumTimeSeconds);
    }


    /**
     * Scales the Fibonacci sequence by `$multiplier`, capped at `$maximumTimeSeconds`, so the first two failures
     * wait the same amount before the growth begins.
     *
     * @throws \InvalidArgumentException if `$multiplier` is not greater than 0, if `$maximumTimeSeconds` is
     * negative, or if `$multiplier` is not less than `$maximumTimeSeconds`
     */
    public static function fibonacciWait(float $multiplier = 1, float $maximumTimeSeconds = PHP_FLOAT_MAX): WaitStrategy
    {
        return new FibonacciWaitStrategy($multiplier, $maximumTimeSeconds);
    }

    /**
     * Derives the wait from the throwable itself; an attempt that succeeded, or that failed with anything other
     * than `$exceptionClass`, waits 0.
     *
     * @param string $exceptionClass only a throwable of this class, or a subclass of it, gets a computed wait
     * @param callable $function `fn(\Throwable): float` returning seconds
     * @throws \InvalidArgumentException if `$exceptionClass` does not name a throwable class; an interface
     * extending `\Throwable` does not qualify
     */
    public static function exceptionWait(string $exceptionClass, callable $function): WaitStrategy
    {
        return new ExceptionWaitStrategy($exceptionClass, $function);
    }

    /**
     * Sums the waits every given strategy computes for the same attempt. Nothing clamps the total, so a strategy
     * returning a negative wait can drag it below zero and make `SleepStrategy` throw.
     *
     * @throws \InvalidArgumentException if no strategies are given
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
        $random = (float) rand() / (float) getrandmax();

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
        Validator::greaterThan(0)->setName('$multiplier')->check($multiplier);
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
        Validator::greaterThan(0)->setName('$multiplier')->check($multiplier);
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
        } elseif ($n === 1) {
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

    /**
     * @var callable(\Throwable): float
     */
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
    /**
     * @var list<WaitStrategy>
     */
    private array $waitStrategies;

    public function __construct(WaitStrategy ...$waitStrategies)
    {
        Validator::notEmpty()->setName('$waitStrategies')->check($waitStrategies);

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
