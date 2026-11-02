<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Ai\ExecutionModels;

return new class extends Migration
{
    public function up(): void
    {
        $driver = ExecutionModels::driver();
        $schema = Schema::connection(config('laravel-ai.storage.connection'));
        $name = (string) config('laravel-ai.executions_table', 'ai_executions');

        if ($driver === 'mongodb') {
            $schema->create($name, function (MongoDB\Laravel\Schema\Blueprint $collection): void {
                $collection->index(['actor_id', 'created_at']);
                $collection->index(['status', 'created_at']);
            });

            return;
        }

        $schema->create($name, function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('assistant');
            $table->string('actor_id')->nullable();
            $table->string('provider');
            $table->string('model')->nullable();
            $table->string('status', 32);
            $table->json('tools');
            $table->json('usage')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->string('error_type')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['actor_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection(config('laravel-ai.storage.connection'))
            ->dropIfExists((string) config('laravel-ai.executions_table', 'ai_executions'));
    }
};
