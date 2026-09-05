<?php

declare(strict_types=1);

namespace tagadvance\elephant\retry;

/**
 * Wraps whatever a retried call threw. `Retryer` always constructs it with an empty message, so the detail is
 * on the cause and reachable only through `getPrevious()`.
 */
class ExecutionException extends \Exception {}
