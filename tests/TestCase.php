<?php

namespace Tegos\LaravelTelescopeFlusher\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Tegos\LaravelTelescopeFlusher\LaravelTelescopeFlusherServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [LaravelTelescopeFlusherServiceProvider::class];
    }
}
