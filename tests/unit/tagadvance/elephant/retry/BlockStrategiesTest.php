<?php

namespace tagadvance\elephant\retry;

use PHPUnit\Framework\TestCase;

class BlockStrategiesTest extends TestCase
{
	function testSleepStrategy()
	{
		$start = microtime(true);

		$seconds = .05;
		BlockStrategies::sleepStrategy()->block($seconds);

		$stop = microtime(true);

		$expected = 0;
		$actual = $stop - $start;
		$this->assertGreaterThan($expected, $actual);
	}
}
