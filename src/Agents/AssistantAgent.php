<?php

namespace Modules\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Modules\Ai\Assistant;

class AssistantAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * @param  list<Tool>  $resolvedTools
     * @param  list<Message>  $history
     */
    public function __construct(
        public readonly Assistant $definition,
        private array $resolvedTools = [],
        private array $history = [],
    ) {}

    public function instructions(): string
    {
        return $this->definition->instructions;
    }

    /** @return list<Message> */
    public function messages(): iterable
    {
        return $this->history;
    }

    /** @return list<Tool> */
    public function tools(): iterable
    {
        return $this->resolvedTools;
    }

    public function maxSteps(): int
    {
        return $this->definition->maxSteps;
    }

    public function maxTokens(): int
    {
        return $this->definition->maxTokens;
    }
}
