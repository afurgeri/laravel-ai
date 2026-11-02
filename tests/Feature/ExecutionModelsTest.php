<?php

use Modules\Ai\ExecutionModels;

test('rejects unsupported storage drivers and incompatible connections', function (string $driver, string $connection, string $message) {
    config([
        'laravel-ai.storage.driver' => $driver,
        'laravel-ai.storage.connection' => $connection,
        'database.connections.mongodb' => ['driver' => 'mongodb'],
    ]);

    expect(fn () => ExecutionModels::execution())->toThrow(InvalidArgumentException::class, $message);
})->with([
    'unsupported storage' => ['redis', 'sqlite', 'must be sql or mongodb'],
    'unknown connection' => ['sql', 'missing', 'missing or unsupported'],
    'mongo storage on sql' => ['mongodb', 'sqlite', 'does not match'],
    'sql storage on mongo' => ['sql', 'mongodb', 'does not match'],
]);

test('uses the configured table and connection for execution records', function () {
    config(['laravel-ai.executions_table' => 'custom_ai_executions']);
    $this->useStorage('sql');
    $class = ExecutionModels::execution();
    $execution = new $class;
    $execution->forceFill([
        'id' => '019a3f47-9015-7000-8000-000000000001',
        'assistant' => 'catalog',
        'provider' => 'openai',
        'status' => 'running',
        'tools' => [],
    ])->save();

    $this->assertDatabaseHas('custom_ai_executions', ['assistant' => 'catalog'], 'sqlite');
});
