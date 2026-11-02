<?php

namespace Modules\Ai;

use Laravel\Ai\Responses\AgentResponse;

final readonly class RunResult
{
    public function __construct(
        public string $executionId,
        public AgentResponse $response,
    ) {}
}
