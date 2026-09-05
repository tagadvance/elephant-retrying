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
 * An attempt of a call, which resulted either in a result returned by the call, or in a Throwable thrown by the call.
 */
interface Attempt
{
    /**
     * The outcome as the caller of `Retryer::call()` sees it: the result, or the failure rethrown wrapped.
     *
     * @throws ExecutionException if the call threw; the original throwable is the wrapped cause
     */
    public function get(): mixed;

    public function hasResult(): bool;

    public function hasException(): bool;

    /**
     * The result, without the `ExecutionException` wrapping that `get()` applies to a failure.
     *
     * @throws IllegalStateException if the call threw rather than returning, as `hasException()` reports
     */
    public function getResult(): mixed;

    /**
     * The throwable the call raised, unwrapped.
     *
     * @throws IllegalStateException if the call returned rather than throwing, as `hasResult()` reports
     */
    public function getExceptionCause(): \Throwable;

    /**
     * The number, starting from 1, of this attempt.
     */
    public function getAttemptNumber(): int;

    /**
     * The delay since the start of the first attempt, in seconds.
     */
    public function getDelaySinceFirstAttempt(): float;
}
