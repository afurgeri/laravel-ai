<?php

use AiTests\Fixtures\ProductLookup;
use Modules\Ai\ToolRegistry;

test('rejects duplicate tool aliases or classes', function (string $alias) {
    $tools = app(ToolRegistry::class);
    $tools->register('products.lookup', ProductLookup::class);

    expect(fn () => $tools->register($alias, ProductLookup::class))->toThrow(InvalidArgumentException::class, 'already registered');
})->with(['products.lookup', 'another.alias']);

test('resolves fresh tool instances for each execution without duplicating repeated aliases', function () {
    $tools = app(ToolRegistry::class);
    $tools->register('products.lookup', ProductLookup::class);

    $first = $tools->resolve(['products.lookup', 'products.lookup']);
    $second = $tools->resolve(['products.lookup']);

    expect($first)->toHaveCount(1);
    expect($first[0])->toBeInstanceOf(ProductLookup::class)->not->toBe($second[0]);
});

test('rejects empty tool aliases', function () {
    expect(fn () => app(ToolRegistry::class)->register('', ProductLookup::class))
        ->toThrow(InvalidArgumentException::class, 'named, concrete tool');
});
