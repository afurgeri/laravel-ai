<?php

namespace Modules\Ai;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Laravel\Ai\Contracts\Tool;
use ReflectionClass;

final class ToolRegistry
{
    /** @var array<string, class-string<Tool>> */
    private array $tools = [];

    public function __construct(private Container $container) {}

    /**
     * @param  class-string<Tool>  $toolClass
     */
    public function register(string $name, string $toolClass): void
    {
        $reflection = new ReflectionClass($toolClass);

        if ($name === '' || ! $reflection->isInstantiable() || ! $reflection->implementsInterface(Tool::class)) {
            throw new InvalidArgumentException('Register a named, concrete tool implementing '.Tool::class.'.');
        }

        if (isset($this->tools[$name]) || in_array($toolClass, $this->tools, true)) {
            throw new InvalidArgumentException('The tool name or class is already registered.');
        }

        $this->tools[$name] = $toolClass;
    }

    /**
     * @param  list<string>  $names
     * @return list<Tool>
     */
    public function resolve(array $names): array
    {
        return array_map(function (string $name): Tool {
            $class = $this->tools[$name] ?? throw new InvalidArgumentException("AI tool [{$name}] is not registered.");

            return $this->container->make($class);
        }, array_values(array_unique($names)));
    }
}
