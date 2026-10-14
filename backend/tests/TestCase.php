<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Run Sanctum migrations for device token authentication
        $this->artisan('migrate', [
            '--path' => 'vendor/laravel/sanctum/database/migrations',
            '--force' => true,
        ]);
    }
}
