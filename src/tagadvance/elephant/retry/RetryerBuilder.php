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
 * Configures and creates a `Retryer`. Conditions added by the `retryIf*` methods are OR'd together, and with none
 * of them configured the retryer never retries at all.
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

    public static function newBuilder(): self
    {
        return new self();
    }

    /**
     * Registers a listener to be notified of every attempt. Unlike the strategy setters this accumulates, so
     * calling it twice registers two listeners rather than throwing.
     */
    public function withRetryListener(RetryListener $listener): self
    {
        $this->listeners[] = $listener;

        return $this;
    }

    /**
     * Defaults to `WaitStrategies::noWait()`, which retries immediately.
     *
     * @throws IllegalStateException if a wait strategy has already been set; this is a one-shot setter
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
     * Defaults to `StopStrategies::neverStop()`, which retries forever.
     *
     * @throws IllegalStateException if a stop strategy has already been set; this is a one-shot setter
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
     * Defaults to `BlockStrategies::sleepStrategy()`, which blocks with `usleep()`.
     *
     * @throws IllegalStateException if a block strategy has already been set; this is a one-shot setter
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
     * Retries when the call throws `$exceptionClass` or a subclass of it.
     *
     * @throws \InvalidArgumentException if `$exceptionClass` does not name a throwable class; an interface
     * extending `\Throwable` does not qualify
     */
    public function retryIfExceptionOfType(string $exceptionClass): self
    {
        $this->rejectionPredicate = self::orPredicate($this->rejectionPredicate, self::exceptionClassPredicate($exceptionClass));

        return $this;
    }

    /**
     * Retries when the call throws and the predicate accepts the throwable.
     *
     * @param callable $exceptionPredicate `fn(\Throwable): bool`, receiving the original throwable rather than
     * the `ExecutionException` wrapper
     */
    public function retryIfException(callable $exceptionPredicate): self
    {
        $this->rejectionPredicate = self::orPredicate($this->rejectionPredicate, self::exceptionPredicate($exceptionPredicate));

        return $this;
    }

    /**
     * Retries when the call returns and the predicate accepts the returned value.
     *
     * @param callable $resultPredicate `fn(mixed): bool`; true means the result is unacceptable and warrants
     * another attempt
     */
    public function retryIfResult(callable $resultPredicate): self
    {
        $this->rejectionPredicate = self::orPredicate($this->rejectionPredicate, self::resultPredicate($resultPredicate));

        return $this;
    }

    /**
     * Creates the retryer, substituting each documented default for any strategy left unset.
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
