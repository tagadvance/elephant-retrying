<?php

namespace tagadvance\elephant\retry;

use PHPUnit\Framework\TestCase;

class RetryerBuilderTest extends TestCase
{
    public function testNewBuilder()
    {
        $builder = RetryerBuilder::newBuilder();

        $this->assertInstanceOf(RetryerBuilder::class, $builder);
    }

    public function testWithRetryListener()
    {
        $listener = \Mockery::mock(RetryListener::class);
        $builder = RetryerBuilder::newBuilder();

        $this->assertSame($builder, $builder->withRetryListener($listener));
    }

    public function testWithWaitStrategyValidation()
    {
        $this->expectException(IllegalStateException::class);
        $this->expectExceptionMessage('a wait strategy has already been set');

        $strategy = \Mockery::mock(WaitStrategy::class);
        RetryerBuilder::newBuilder()->withWaitStrategy($strategy)->withWaitStrategy($strategy);
    }

    public function testWithStopStrategyValidation()
    {
        $this->expectException(IllegalStateException::class);
        $this->expectExceptionMessage('a stop strategy has already been set');

        $strategy = \Mockery::mock(StopStrategy::class);
        RetryerBuilder::newBuilder()->withStopStrategy($strategy)->withStopStrategy($strategy);
    }

    public function testWithBlockStrategyValidation()
    {
        $this->expectException(IllegalStateException::class);
        $this->expectExceptionMessage('a block strategy has already been set');

        $strategy = \Mockery::mock(BlockStrategy::class);
        RetryerBuilder::newBuilder()->withBlockStrategy($strategy)->withBlockStrategy($strategy);
    }

    public function testRetryIfExceptionOfTypeValidation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$exceptionClass must be valid');

        RetryerBuilder::newBuilder()->retryIfExceptionOfType('Foo');
    }

    public function testRetryIfExceptionOfType()
    {
        $builder = RetryerBuilder::newBuilder();

        $this->assertSame($builder, $builder->retryIfExceptionOfType(\Throwable::class));
    }

    public function testRetryIfException()
    {
        $builder = RetryerBuilder::newBuilder();

        $this->assertSame($builder, $builder->retryIfException(fn(\Throwable $t) => true));
    }

    public function testRetryIfResult()
    {
        $builder = RetryerBuilder::newBuilder();

        $this->assertSame($builder, $builder->retryIfResult(fn($result) => true));
    }
}
