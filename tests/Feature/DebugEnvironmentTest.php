<?php

namespace Tests\Feature;

use Tests\TestCase;

class DebugEnvironmentTest extends TestCase
{
    public function test_check_database_config(): void
    {
        echo "\n";
        echo "APP_ENV: " . config('app.env') . "\n";
        echo "DB_CONNECTION: " . config('database.default') . "\n";
        echo "DB_DATABASE: " . config('database.connections.' . config('database.default') . '.database') . "\n";
        echo "\n";

        $this->assertTrue(true);
    }
}
