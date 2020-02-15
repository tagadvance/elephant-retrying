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
 * An attempt of a call, which resulted either in a result returned by the call, or in a Throwable thrown by the call.
 */
interface Attempt
{

	/**
	 * Returns the result of the attempt, if any.
	 *
	 * @return mixed the result of the attempt
	 * @throws ExecutionException if an exception was thrown by the attempt. The thrown exception is set as the cause of the ExecutionException
	 */
	public function get();

	/**
	 * Tells if the call returned a result or not
	 *
	 * @return bool <code>true</code> if the call returned a result, <code>false</code> if it threw an exception
	 */
	public function hasResult(): bool;

	/**
	 * Tells if the call threw an exception or not
	 *
	 * @return bool <code>true</code> if the call threw an exception, <code>false</code> if it returned a result
	 */
	public function hasException(): bool;

	/**
	 * Gets the result of the call
	 *
	 * @return mixed the result of the call
	 * @throws IllegalStateException if the call didn't return a result, but threw an exception, as indicated by {@link #hasResult()}
	 */
	public function getResult();

	/**
	 * Gets the exception thrown by the call
	 *
	 * @return \Throwable the exception thrown by the call
	 * @throws IllegalStateException if the call didn't throw an exception, as indicated by {@link #hasException()}
	 */
	public function getExceptionCause(): \Throwable;

	/**
	 * The number, starting from 1, of this attempt.
	 *
	 * @return int the attempt number
	 */
	public function getAttemptNumber(): int;

	/**
	 * The delay since the start of the first attempt, in milliseconds.
	 *
	 * @return the delay since the start of the first attempt, in milliseconds
	 */
	public function getDelaySinceFirstAttempt(): int;
}
