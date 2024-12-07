<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Force SQLite to use in-memory database
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        // Ensure foreign key constraints are enabled
        DB::statement('PRAGMA foreign_keys = ON');
    }

    protected function refreshTestDatabase()
    {
        // Run all migrations in order
        $this->artisan('migrate:fresh');
    }
}
