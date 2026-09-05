<?php

declare(strict_types=1);

namespace tagadvance\elephant\retry;

/**
 * Signals a call made in a state that cannot honour it: reading the wrong side of an `Attempt`, or setting the
 * same kind of strategy on a `RetryerBuilder` twice.
 */
class IllegalStateException extends \RuntimeException {}
