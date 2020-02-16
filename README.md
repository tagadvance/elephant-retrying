[![Build Status](https://travis-ci.org/tagadvance/elephant-retrying.svg?branch=master)](https://travis-ci.org/tagadvance/elephant-retrying)
[![Coverage Status](https://coveralls.io/repos/github/tagadvance/elephant-retrying/badge.svg?branch=master)](https://coveralls.io/github/tagadvance/elephant-retrying?branch=master)
[![License](http://img.shields.io/badge/license-apache%202-brightgreen.svg)](https://github.com/tagadvance/elephant-retrying/blob/master/LICENSE)

## What is this?
The elephant-retrying module provides a general purpose method for retrying arbitrary PHP code with specific stop, retry, and exception handling capabilities.

This is a fork of the excellent guava-retrying code posted [here](https://github.com/rholder/guava-retrying) by Ray Holder (rholder).

## Composer
```bash
composer require tagadvance/elephant-retrying
```

## Quickstart
A minimal sample of some of the functionality would look like:

```php
$retryer = RetryerBuilder::newBuilder()
	->retryIfResult('is_null')
	->retryIfExceptionOfType(\RuntimeException::class)
	->withStopStrategy(StopStrategies . stopAfterAttempt(3))
	->build();
try {
	$retryer->call(fn() => true); // do something useful here
} catch (RetryException $e) {
	print $e->getTraceAsString();
} catch (ExecutionException $e) {
	print $e->getTraceAsString();
}
```

This will retry whenever the result of the `Callable` is null, if an `IOException` is thrown, or if any other
`RuntimeException` is thrown from the `call()` method. It will stop after attempting to retry 3 times and throw a
`RetryException` that contains information about the last failed attempt. If any other `Exception` pops out of the
`call()` method it's wrapped and rethrown in an `ExecutionException`.

## Exponential Backoff

Create a `Retryer` that retries forever, waiting after every failed retry in increasing exponential backoff intervals
until at most 5 minutes. After 5 minutes, retry from then on in 5 minute intervals.

```php
$maximumWaitTimeSeconds = 300; // 5 minutes
$retryer = RetryerBuilder::newBuilder()
        ->retryIfExceptionOfType(\RuntimeException::class)
        ->withWaitStrategy(WaitStrategies::exponentialWait(1, $maximumWaitTimeSeconds))
        ->withStopStrategy(StopStrategies::neverStop())
        ->build();
```
You can read more about [exponential backoff](http://en.wikipedia.org/wiki/Exponential_backoff) and the historic role
it played in the development of TCP/IP in [Congestion Avoidance and Control](http://ee.lbl.gov/papers/congavoid.pdf).

## Fibonacci Backoff

Create a `Retryer` that retries forever, waiting after every failed retry in increasing Fibonacci backoff intervals
until at most 2 minutes. After 2 minutes, retry from then on in 2 minute intervals.

```php
$maximumWaitTimeSeconds = 120; // 2 minutes
$retryer = RetryerBuilder::newBuilder()
	->retryIfExceptionOfType(\RuntimeException::class)
	->withWaitStrategy(WaitStrategies::fibonacciWait(1, $maximumWaitTimeSeconds))
	->withStopStrategy(StopStrategies::neverStop())
	->build();
```

Similar to the `ExponentialWaitStrategy`, the `FibonacciWaitStrategy` follows a pattern of waiting an increasing amount
of time after each failed attempt.

Instead of an exponential function it's (obviously) using a
[Fibonacci sequence](https://en.wikipedia.org/wiki/Fibonacci_numbers) to calculate the wait time.

Depending on the problem at hand, the `FibonacciWaitStrategy` might perform better and lead to better throughput than
the `ExponentialWaitStrategy` - at least according to
[A Performance Comparison of Different Backoff Algorithms under Different Rebroadcast Probabilities for MANETs](http://www.comp.leeds.ac.uk/ukpew09/papers/12.pdf).

The implementation of `FibonacciWaitStrategy` is using an iterative version of the Fibonacci because a (naive) recursive
version will lead to a [StackOverflowError](http://docs.oracle.com/javase/7/docs/api/java/lang/StackOverflowError.html)
at a certain point (although very unlikely with useful parameters for retrying).

Inspiration for this implementation came from [Efficient retry/backoff mechanisms](https://paperairoplane.net/?p=640).

### Source
`git clone git@github.com:tagadvance/elephant-retrying.git`

### Clean, Install, and Test
`./make`

## License
The guava-retrying module is released under version 2.0 of the
[Apache License](http://www.apache.org/licenses/LICENSE-2.0).
