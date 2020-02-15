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
 * Factory class for `BlockStrategy` instances.
 *
 * @see BlockStrategy
 */
final class BlockStrategies
{
	private function __construct()
	{
	}

	/**
	 * Returns a block strategy that puts the current thread to sleep between retries.
	 *
	 * @return BlockStrategy a block strategy that puts the current thread to sleep between retries
	 */
	public static function sleepStrategy(): BlockStrategy
	{
		static $sleepStrategy = null;
		if ($sleepStrategy === null) {
			$sleepStrategy = new SleepStrategy();
		}

		return $sleepStrategy;
	}
}

class SleepStrategy implements BlockStrategy
{
	const MICROSECONDS_PER_SECOND = 1000000.0;

	public function block(float $sleepSeconds): void
	{
		$microseconds = $sleepSeconds * self::MICROSECONDS_PER_SECOND;
		usleep($microseconds);
	}
}
