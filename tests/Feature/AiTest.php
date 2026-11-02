<?php

use AiTests\Fixtures\ProductLookup;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Modules\Ai\Agents\AssistantAgent;
use Modules\Ai\Agents\StructuredAssistantAgent;
use Modules\Ai\Ai;
use Modules\Ai\Assistant;
use Modules\Ai\Enums\ExecutionStatus;
use Modules\Ai\ExecutionModels;
use Modules\Ai\ToolRegistry;

test('runs with either provider and records usage without storing content', function (string $driver, string $provider) {
    $this->useStorage($driver);
    Http::preventStrayRequests();
    AssistantAgent::fake([
        new AgentResponse('invocation', 'Private product details', new TextUsage(12, 7), new Meta($provider, 'test-model')),
    ])->preventStrayPrompts();

    $result = app(Ai::class)->run(new Assistant('product', 'Prepare a draft.', provider: $provider, model: 'test-model'), 'Sensitive prompt', actorId: 'user-42');

    expect($result->response->text)->toBe('Private product details');
    $execution = ExecutionModels::execution()::query()->findOrFail($result->executionId);
    expect($execution->getAttribute('status'))->toBe(ExecutionStatus::Completed);
    expect($execution->getAttribute('usage'))->toMatchArray(['input_tokens' => 12, 'output_tokens' => 7]);
    expect($execution->getAttribute('actor_id'))->toBe('user-42');
    expect($execution->getAttribute('provider'))->toBe($provider);
    expect($execution->getAttribute('duration_ms'))->toBeGreaterThanOrEqual(0);
    expect(json_encode($execution->getAttributes()))->not->toContain('Sensitive prompt', 'Private product details', 'test-openai-key', 'test-deepseek-key');
    AssistantAgent::assertPrompted('Sensitive prompt');
    Http::assertNothingSent();
})->with('storage')->with(['openai', 'deepseek']);

test('uses the configured provider and model and supplies only the explicit history', function () {
    $this->useStorage('sql');
    config(['laravel-ai.default_provider' => 'deepseek', 'laravel-ai.models.deepseek' => 'configured-model']);
    AssistantAgent::fake(['Draft', 'Another draft'])->preventStrayPrompts();
    $ai = app(Ai::class);
    $assistant = new Assistant('product', 'Explain each field.', maxSteps: 2, maxTokens: 100);

    $ai->run($assistant, 'Refine it', [new Message('user', 'Target small businesses')]);
    $ai->run($assistant, 'Start again');

    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->prompt === 'Refine it'
        && $prompt->provider->name() === 'deepseek'
        && $prompt->model === 'configured-model'
        && $prompt->agent->instructions() === 'Explain each field.'
        && $prompt->agent->maxSteps() === 2
        && $prompt->agent->maxTokens() === 100
        && count($prompt->agent->messages()) === 1);
    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->prompt === 'Start again' && $prompt->agent->messages() === []);
});

test('returns structured suggestions for a form', function () {
    $this->useStorage('sql');
    StructuredAssistantAgent::fake([['description' => 'Booking software', 'audience' => 'Small businesses']])->preventStrayPrompts();
    $assistant = new Assistant('product-form', 'Suggest values, do not invent facts.', schema: fn (JsonSchema $schema): array => [
        'description' => $schema->string()->required(),
        'audience' => $schema->string()->required(),
    ]);

    $result = app(Ai::class)->run($assistant, 'Prepare the form');

    expect($result->response)->toBeInstanceOf(StructuredAgentResponse::class);
    expect($result->response['description'])->toBe('Booking software');
    expect($result->response['audience'])->toBe('Small businesses');
    StructuredAssistantAgent::assertPrompted('Prepare the form');
});

test('executes a registered tool only when the assistant allows it', function () {
    $this->useStorage('sql');
    app(ToolRegistry::class)->register('products.lookup', ProductLookup::class);
    AssistantAgent::fake([
        new ToolCall('call-1', 'ProductLookup', ['code' => 'booking']),
        'Booking software is available.',
    ])->preventStrayPrompts();

    $result = app(Ai::class)->run(new Assistant('catalog', 'Use the catalog.', tools: ['products.lookup']), 'Find booking');

    expect($result->response->text)->toBe('Booking software is available.');
    expect($result->response->toolResults)->toHaveCount(1);
    expect($result->response->toolResults->first()->result)->toBe('Product: booking');
    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => count($prompt->agent->tools()) === 1);
});

test('does not expose registered tools to assistants without an allowlist', function () {
    $this->useStorage('sql');
    app(ToolRegistry::class)->register('products.lookup', ProductLookup::class);
    AssistantAgent::fake(['No tool access'])->preventStrayPrompts();

    app(Ai::class)->run(new Assistant('restricted', 'Explain fields only.'), 'Explain this field');

    AssistantAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->agent->tools() === []);
});

test('rejects unknown tools before creating an execution or contacting a provider', function () {
    $this->useStorage('sql');
    AssistantAgent::fake()->preventStrayPrompts();

    expect(fn () => app(Ai::class)->run(new Assistant('restricted', '', tools: ['unknown']), 'Hello'))
        ->toThrow(InvalidArgumentException::class, 'not registered');

    expect(ExecutionModels::execution()::query()->count())->toBe(0);
    AssistantAgent::assertNeverPrompted();
});

test('records failed executions without persisting exception messages and rethrows the original error', function (string $driver) {
    $this->useStorage($driver);
    $exception = new RuntimeException('Sensitive upstream response');
    AssistantAgent::fake(fn () => throw $exception)->preventStrayPrompts();

    expect(fn () => app(Ai::class)->run(new Assistant('product', ''), 'Sensitive input'))->toThrow($exception);

    $execution = ExecutionModels::execution()::query()->sole();
    expect($execution->getAttribute('status'))->toBe(ExecutionStatus::Failed);
    expect($execution->getAttribute('error_type'))->toBe(RuntimeException::class);
    expect($execution->getAttribute('finished_at'))->not->toBeNull();
    expect(json_encode($execution->getAttributes()))->not->toContain('Sensitive upstream response', 'Sensitive input');
})->with('storage');

test('rejects empty prompts before creating an execution', function () {
    $this->useStorage('sql');

    expect(fn () => app(Ai::class)->run(new Assistant('product', ''), '  '))->toThrow(InvalidArgumentException::class, 'non-empty prompt');

    expect(ExecutionModels::execution()::query()->count())->toBe(0);
});

test('does not contact the provider when execution tracking cannot be persisted', function () {
    AssistantAgent::fake()->preventStrayPrompts();

    expect(fn () => app(Ai::class)->run(new Assistant('product', ''), 'Hello'))
        ->toThrow(QueryException::class);

    AssistantAgent::assertNeverPrompted();
});

test('rejects invalid assistant limits', function (string $limit) {
    expect(fn () => new Assistant('product', '', ...[$limit => 0]))->toThrow(InvalidArgumentException::class, 'must be positive');
})->with(['maxSteps', 'maxTokens', 'timeout']);

test('rejects empty or oversized assistant names', function (string $name) {
    expect(fn () => new Assistant($name, ''))->toThrow(InvalidArgumentException::class, 'between 1 and 255');
})->with(['', '  ', str_repeat('a', 256)]);

test('records pending approvals as paused rather than completed', function () {
    $this->useStorage('sql');
    AssistantAgent::fake([
        AgentResponse::fakeWithPendingApprovals([new PendingApproval('call-1', 'PublishCampaign', [], 'Requires confirmation')]),
    ])->preventStrayPrompts();

    $result = app(Ai::class)->run(new Assistant('campaigns', 'Prepare drafts.'), 'Prepare a campaign');

    expect($result->response->hasPendingApprovals())->toBeTrue();
    expect(ExecutionModels::execution()::query()->findOrFail($result->executionId)->getAttribute('status'))->toBe(ExecutionStatus::Paused);
    AssistantAgent::assertPrompted('Prepare a campaign');
});
