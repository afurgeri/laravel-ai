<?php

namespace Modules\Ai\Concerns;

use Modules\Ai\Enums\ExecutionStatus;

trait HasExecutionAttributes
{
    public function getConnectionName(): ?string
    {
        return config('laravel-ai.storage.connection', 'mysql') ?: null;
    }

    public function getTable(): string
    {
        return config('laravel-ai.executions_table', 'ai_executions');
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ExecutionStatus::class,
            'tools' => 'array',
            'usage' => 'array',
            'duration_ms' => 'integer',
            'finished_at' => 'datetime',
        ];
    }
}
