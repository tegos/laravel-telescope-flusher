<?php

namespace Tegos\LaravelTelescopeFlusher\Tests\Integration;

use Illuminate\Support\Facades\DB;

final class TelescopeFlushCommandIntegrationTest extends IntegrationTestCase
{
    public function test_flush_truncates_all_telescope_tables(): void
    {
        DB::table('telescope_entries')->insert([
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'batch_id' => '550e8400-e29b-41d4-a716-446655440001',
            'type' => 'request',
            'content' => json_encode(['key' => 'value']),
            'created_at' => now(),
        ]);

        DB::table('telescope_entries_tags')->insert([
            'entry_uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'tag' => 'test-tag',
        ]);

        DB::table('telescope_monitoring')->insert([
            'tag' => 'test-tag',
        ]);

        $this->artisan('telescope:flush')
            ->expectsOutput('Telescope entries cleared!')
            ->assertExitCode(0);

        $this->assertSame(0, DB::table('telescope_entries')->count());
        $this->assertSame(0, DB::table('telescope_entries_tags')->count());
        $this->assertSame(0, DB::table('telescope_monitoring')->count());
    }
}
