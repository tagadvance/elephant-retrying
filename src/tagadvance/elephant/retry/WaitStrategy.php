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
 * A strategy used to decide how long to sleep before retrying after a failed attempt.
 */
interface WaitStrategy
{
	/**
	 * Returns the time, in seconds, to sleep before retrying.
	 *
	 * @param Attempt failedAttempt the previous failed `Attempt`
	 * @return float the sleep time before next attempt
	 */
	public function computeSleepTime(Attempt $failedAttempt): float;
}
