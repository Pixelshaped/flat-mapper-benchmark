<?php

namespace App\Tests\Benchmarks;

use App\Kernel;
use Psr\Container\ContainerInterface;

//#[RetryThreshold(2.0)]
//#[Iterations(3)]
abstract class AbstractBench
{
    public function container(): ContainerInterface
    {
        $kernel = new Kernel('test', false);
        $kernel->boot();

        return $kernel->getContainer()->get('test.service_container');
    }
}
