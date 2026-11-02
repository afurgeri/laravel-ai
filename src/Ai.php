<?php

namespace Modules\Ai;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Ai\Messages\Message;
use Modules\Ai\Agents\AssistantAgent;
use Modules\Ai\Agents\StructuredAssistantAgent;
use Modules\Ai\Enums\ExecutionStatus;
use Throwable;

final class Ai
{
    public function __construct(private ToolRegistry $tools) {}

    /**
     * @param  list<Message>  $messages  Trusted history supplied by the application, not persisted by this service.
     */
    public function run(Assistant $assistant, string $prompt, array $messages = [], ?string $actorId = null): RunResult
    {
        if (trim($prompt) === '' || ($actorId !== null && mb_strlen($actorId) > 255)) {
            throw new InvalidArgumentException('Provide a non-empty prompt and an actor ID of at most 255 characters.');
        }

        $resolvedTools = $this->tools->resolve($assistant->tools);
        $provider = $assistant->provider ?? config('laravel-ai.default_provider', 'openai');
        $model = $assistant->model ?? config("laravel-ai.models.{$provider}");
        $agent = $assistant->schema === null
            ? new AssistantAgent($assistant, $resolvedTools, $messages)
            : new StructuredAssistantAgent($assistant, $resolvedTools, $messages);

        $executionClass = ExecutionModels::execution();
        $execution = new $executionClass;
        $id = (string) Str::uuid7();
        $execution->setAttribute($execution->getKeyName(), $id);
        $execution->forceFill([
            'assistant' => $assistant->name,
            'actor_id' => $actorId,
            'provider' => $provider,
            'model' => $model,
            'status' => ExecutionStatus::Running,
            'tools' => array_values(array_unique($assistant->tools)),
        ])->save();

        $startedAt = hrtime(true);

        try {
            $response = $agent->prompt($prompt, provider: $provider, model: $model, timeout: $assistant->timeout);
        } catch (Throwable $exception) {
            $execution->forceFill([
                'status' => ExecutionStatus::Failed,
                'error_type' => $exception::class,
                'duration_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
                'finished_at' => now(),
            ])->save();

            throw $exception;
        }

        $execution->forceFill([
            'provider' => $response->meta->provider ?? $provider,
            'model' => $response->meta->model ?? $model,
            'status' => $response->hasPendingApprovals() ? ExecutionStatus::Paused : ExecutionStatus::Completed,
            'usage' => $response->usage->toArray(),
            'duration_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
            'finished_at' => now(),
        ])->save();

        return new RunResult($id, $response);
    }
}
