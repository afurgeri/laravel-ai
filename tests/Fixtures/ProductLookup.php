<?php

namespace AiTests\Fixtures;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class ProductLookup implements Tool
{
    public function description(): string
    {
        return 'Look up a product by its code.';
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return ['code' => $schema->string()->required()];
    }

    public function handle(Request $request): string
    {
        return 'Product: '.$request['code'];
    }
}
