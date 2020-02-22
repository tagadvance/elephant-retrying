<?php

/*
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

class Utilities
{
	public static function isThrowable(string $class)
	{
		if ($class === \Throwable::class) {
			return true;
		}

		if (class_exists($class)) {
			$interfaces = class_implements($class);

			return in_array(\Throwable::class, $interfaces);
		}

		return false;
	}
}
