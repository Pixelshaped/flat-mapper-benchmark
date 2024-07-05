<?php

namespace App\Tests\Benchmarks;

use App\Kernel;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\RetryThreshold;
use Psr\Container\ContainerInterface;

#[RetryThreshold(1.0)]
#[Iterations(3)]
#[BeforeMethods('setUp')]
abstract class AbstractBench
{
    public function container(): ContainerInterface
    {
        $kernel = new Kernel('test', false);
        $kernel->boot();

        return $kernel->getContainer()->get('test.service_container');
    }

    abstract public function setUp(): void;
}
