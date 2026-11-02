<?php

namespace AiTests;

use Illuminate\Support\Facades\DB;
use Laravel\Ai\AiServiceProvider as SdkServiceProvider;
use Modules\Ai\AiServiceProvider;
use MongoDB\Laravel\MongoDBServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [MongoDBServiceProvider::class, SdkServiceProvider::class, AiServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('laravel-ai.storage.connection', 'sqlite');
        $app['config']->set('ai.providers.openai.key', 'test-openai-key');
        $app['config']->set('ai.providers.deepseek.key', 'test-deepseek-key');
    }

    protected function useStorage(string $driver): void
    {
        if ($driver === 'mongodb' && ! getenv('MONGODB_TEST_URI')) {
            $this->markTestSkipped('Set MONGODB_TEST_URI to run against an isolated MongoDB test database.');
        }

        config([
            'laravel-ai.storage.driver' => $driver,
            'laravel-ai.storage.connection' => $driver === 'mongodb' ? 'ai_test' : 'sqlite',
            'database.connections.ai_test' => [
                'driver' => 'mongodb',
                'dsn' => getenv('MONGODB_TEST_URI') ?: 'mongodb://127.0.0.1:27017',
                'database' => 'laravel_ai_test_'.bin2hex(random_bytes(8)),
            ],
        ]);

        if ($driver === 'mongodb') {
            $this->beforeApplicationDestroyed(fn () => DB::connection('ai_test')->getDatabase()->drop());
        }

        (require __DIR__.'/../database/migrations/2026_11_02_000000_create_ai_executions_table.php')->up();
    }
}
