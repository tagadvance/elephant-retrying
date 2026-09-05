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
 * A strategy used to decide if a retryer must stop retrying after a failed attempt or not.
 */
interface StopStrategy
{
    /**
     * Whether the retryer should give up rather than make a further attempt.
     *
     * @param Attempt $failedAttempt the attempt that has just been rejected, not the one being contemplated
     */
    public function shouldStop(Attempt $failedAttempt): bool;
}
