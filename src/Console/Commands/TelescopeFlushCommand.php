<?php

namespace Tegos\LaravelTelescopeFlusher\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'telescope:flush')]
final class TelescopeFlushCommand extends Command
{
    protected $signature = 'telescope:flush';

    protected $description = 'Completely flush all Telescope data from the database';

    public function handle(): int
    {
        if (!App::isLocal()) {
            $this->error('This command is only allowed in local environments.');
            return self::INVALID;
        }

        // Check if Telescope tables exist
        if (!$this->isTelescopeInstalled()) {
            $this->error('Telescope is not installed or its tables are missing.');
            return self::FAILURE;
        }

        DB::getSchemaBuilder()->withoutForeignKeyConstraints(function () {
            DB::table('telescope_entries')->truncate();
            DB::table('telescope_entries_tags')->truncate();
            DB::table('telescope_monitoring')->truncate();
        });

        // Optimize only if using MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement('OPTIMIZE TABLE telescope_entries');
        }

        $this->info('Telescope entries cleared!');

        return self::SUCCESS;
    }

    private function isTelescopeInstalled(): bool
    {
        return DB::getSchemaBuilder()->hasTable('telescope_entries') &&
            DB::getSchemaBuilder()->hasTable('telescope_entries_tags') &&
            DB::getSchemaBuilder()->hasTable('telescope_monitoring');
    }
}
