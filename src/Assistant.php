<?php

namespace Modules\Ai;

use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use InvalidArgumentException;

final readonly class Assistant
{
    /**
     * @param  list<string>  $tools  Registered tools explicitly allowed for this assistant.
     * @param  (Closure(JsonSchema): array<string, Type>)|null  $schema
     */
    public function __construct(
        public string $name,
        public string $instructions,
        public ?string $provider = null,
        public ?string $model = null,
        public array $tools = [],
        public ?Closure $schema = null,
        public int $maxSteps = 5,
        public int $maxTokens = 2048,
        public int $timeout = 60,
    ) {
        if (trim($name) === '' || mb_strlen($name) > 255) {
            throw new InvalidArgumentException('An assistant name must contain between 1 and 255 characters.');
        }

        if ($maxSteps < 1 || $maxTokens < 1 || $timeout < 1) {
            throw new InvalidArgumentException('Assistant step, token and timeout limits must be positive.');
        }
    }
}
