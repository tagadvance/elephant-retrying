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
 * Executes a call and retries it until an attempt is accepted or the stop strategy gives up, waiting between
 * attempts as the wait and block strategies direct. Build one with `RetryerBuilder` rather than by hand.
 *
 * @see RetryerBuilder
 */
final class Retryer
{
    private StopStrategy $stopStrategy;
    private WaitStrategy $waitStrategy;
    private BlockStrategy $blockStrategy;

    /**
     * @var callable(Attempt): bool
     */
    private $rejectionPredicate;

    /**
     * Not a list: spreading a string-keyed array into the variadic constructor keeps those
     * keys.
     *
     * @var array<array-key, RetryListener>
     */
    private array $listeners;

    /**
     * @param callable $rejectionPredicate `fn(Attempt): bool`; returning true rejects the attempt and triggers a
     * retry, unless the stop strategy says otherwise
     */
    public function __construct(
        StopStrategy $stopStrategy,
        WaitStrategy $waitStrategy,
        BlockStrategy $blockStrategy,
        callable $rejectionPredicate,
        RetryListener ...$listeners,
    ) {
        $this->stopStrategy = $stopStrategy;
        $this->waitStrategy = $waitStrategy;
        $this->blockStrategy = $blockStrategy;
        $this->rejectionPredicate = $rejectionPredicate;
        $this->listeners = $listeners;
    }

    /**
     * Runs `$callable` until an attempt is accepted, blocking the caller for the whole sequence including every
     * wait. Anything thrown by a listener, by the rejection predicate or by a strategy propagates as-is.
     *
     * @throws ExecutionException if the accepted attempt was one that threw; the original throwable is the
     * wrapped cause
     * @throws RetryException if the stop strategy gave up, carrying the last failed attempt
     */
    public function call(callable $callable): mixed
    {
        $startTime = microtime(true);

        for ($attemptNumber = 1; ; $attemptNumber++) {
            try {
                $result = $callable();
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
                throw new RetryException(null, $attemptNumber, $attempt);
            }

            $sleepTime = $this->waitStrategy->computeSleepTime($attempt);
            $this->blockStrategy->block($sleepTime);
        }
    }

    /**
     * Binds `$callable` to this retryer so it can be handed anywhere a plain callable is wanted; invoking the
     * returned closure is exactly a `call()`, retries, blocking and exceptions included.
     */
    public function wrap(callable $callable): callable
    {
        return fn() => $this->call($callable);
    }
}

/**
 * The `Attempt` handed to listeners and strategies when the call returned a value.
 */
class ResultAttempt implements Attempt
{
    private mixed $result;
    private int $attemptNumber;
    private float $delaySinceFirstAttempt;

    public function __construct(mixed $result, int $attemptNumber, float $delaySinceFirstAttempt)
    {
        $this->result = $result;
        $this->attemptNumber = $attemptNumber;
        $this->delaySinceFirstAttempt = $delaySinceFirstAttempt;
    }

    public function get(): mixed
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

    public function getResult(): mixed
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

/**
 * The `Attempt` handed to listeners and strategies when the call threw.
 */
final class ExceptionAttempt implements Attempt
{
    private ExecutionException $e;
    private \Throwable $cause;
    private int $attemptNumber;
    private float $delaySinceFirstAttempt;

    public function __construct(\Throwable $cause, int $attemptNumber, float $delaySinceFirstAttempt)
    {
        $this->cause = $cause;
        $this->e = new ExecutionException('', 0, $cause);
        $this->attemptNumber = $attemptNumber;
        $this->delaySinceFirstAttempt = $delaySinceFirstAttempt;
    }

    public function get(): mixed
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

    public function getResult(): mixed
    {
        throw new IllegalStateException('The attempt resulted in an exception, not in a result');
    }

    public function getExceptionCause(): \Throwable
    {
        return $this->cause;
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
