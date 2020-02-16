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
 * This is a strategy used to decide how a retryer should block between retry attempts.
 *
 * @see https://www.php.net/usleep usleep
 */
interface BlockStrategy
{
	/**
	 * Attempt to block for the designated amount of time. Implementations that don't block or otherwise delay the
	 * processing from within this method for the given sleep duration can significantly modify the behavior of any
	 * configured `WaitStrategy`. Caution is advised when generating your own implementations.
	 *
	 * @param float $sleepSeconds the computed sleep duration in seconds
	 *
	 * @see WaitStrategy
	 */
	public function block(float $sleepSeconds): void;
}
