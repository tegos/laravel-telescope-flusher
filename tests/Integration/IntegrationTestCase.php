<?php

namespace Tegos\LaravelTelescopeFlusher\Tests\Integration;

use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Tegos\LaravelTelescopeFlusher\LaravelTelescopeFlusherServiceProvider;

abstract class IntegrationTestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [LaravelTelescopeFlusherServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['env'] = 'local';
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'mysql'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'telescope_test'),
            'username' => env('DB_USERNAME', 'telescope'),
            'password' => env('DB_PASSWORD', 'secret'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTelescopeTables();
    }

    protected function tearDown(): void
    {
        $this->dropTelescopeTables();
        parent::tearDown();
    }

    private function createTelescopeTables(): void
    {
        Schema::create('telescope_entries', function ($table) {
            $table->bigIncrements('sequence');
            $table->uuid('uuid')->unique();
            $table->uuid('batch_id');
            $table->string('family_hash')->nullable()->index();
            $table->boolean('should_display_on_index')->default(true);
            $table->string('type', 20);
            $table->longText('content');
            $table->dateTime('created_at')->nullable()->index();
        });

        Schema::create('telescope_entries_tags', function ($table) {
            $table->uuid('entry_uuid');
            $table->string('tag');
            $table->index(['entry_uuid', 'tag']);
            $table->index('tag');
        });

        Schema::create('telescope_monitoring', function ($table) {
            $table->string('tag');
        });
    }

    private function dropTelescopeTables(): void
    {
        Schema::dropIfExists('telescope_entries_tags');
        Schema::dropIfExists('telescope_monitoring');
        Schema::dropIfExists('telescope_entries');
    }
}
