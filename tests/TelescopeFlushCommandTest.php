<?php

namespace Tegos\LaravelTelescopeFlusher\Tests;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

final class TelescopeFlushCommandTest extends TestCase
{
    public function test_telescope_flush_command_runs_in_local_environment(): void
    {
        App::shouldReceive('isLocal')->once()->andReturn(true);
        DB::shouldReceive('getSchemaBuilder->withoutForeignKeyConstraints')->once()->andReturnUsing(function ($callback) {
            $callback();
        });
        DB::shouldReceive('table')->times(3)->andReturnSelf();
        DB::shouldReceive('truncate')->times(3);
        DB::shouldReceive('getDriverName')->once()->andReturn('mysql');
        DB::shouldReceive('statement')->once()->with('OPTIMIZE TABLE telescope_entries');

        $this->artisan('telescope:flush')
            ->expectsOutput('Telescope entries cleared!')
            ->assertExitCode(0);
    }
}
