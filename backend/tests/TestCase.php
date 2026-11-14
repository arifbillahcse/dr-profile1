<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if (! $app->environment('testing') || ($connection === 'sqlite' ? $database !== ':memory:' : ! str_ends_with((string) $database, '_test'))) {
            throw new RuntimeException('Tests require APP_ENV=testing and an in-memory SQLite or dedicated *_test database.');
        }

        return $app;
    }
}
