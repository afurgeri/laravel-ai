<?php

namespace Modules\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\HasStructuredOutput;
use LogicException;

class StructuredAssistantAgent extends AssistantAgent implements HasStructuredOutput
{
    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $definition = $this->definition->schema ?? throw new LogicException('A structured assistant requires a schema.');

        return $definition($schema);
    }
}
