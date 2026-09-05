<?php

namespace tagadvance\elephant\retry;

use PHPUnit\Framework\TestCase;

class RetryExceptionTest extends TestCase
{
    public function testConstructorWithMessage()
    {
        $expected = 'This is a message';

        $exception = new RetryException($expected, 0, $this->createAttempt());

        $actual = $exception->getMessage();

        $this->assertEquals($expected, $actual);
    }

    public function testConstructorWithNullMessage()
    {
        $expected = 'Retrying failed to complete successfully after 0 attempts.';

        $exception = new RetryException(null, 0, $this->createAttempt());

        $actual = $exception->getMessage();

        $this->assertEquals($expected, $actual);
    }

    private function createAttempt(): Attempt
    {
        $attempt = $this->createStub(Attempt::class);
        $attempt->method('hasException')->willReturn(true);
        $attempt->method('getExceptionCause')->willReturn(new \Exception());

        return $attempt;
    }
}
