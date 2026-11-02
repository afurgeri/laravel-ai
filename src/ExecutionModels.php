<?php

namespace Modules\Ai;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RuntimeException;

final class ExecutionModels
{
    public static function driver(): string
    {
        $driver = config('laravel-ai.storage.driver', 'sql');
        $connection = config('laravel-ai.storage.connection') ?: config('database.default');
        $connectionDriver = config("database.connections.{$connection}.driver");

        if (! in_array($driver, ['sql', 'mongodb'], true)) {
            throw new InvalidArgumentException('AI storage must be sql or mongodb.');
        }

        if (! in_array($connectionDriver, ['mysql', 'mariadb', 'sqlite', 'pgsql', 'sqlsrv', 'mongodb'], true)) {
            throw new InvalidArgumentException('The configured AI database connection is missing or unsupported.');
        }

        if (($driver === 'mongodb') !== ($connectionDriver === 'mongodb')) {
            throw new InvalidArgumentException('AI storage driver does not match its database connection.');
        }

        if ($driver === 'mongodb' && ! class_exists(\MongoDB\Laravel\Eloquent\Model::class)) {
            throw new RuntimeException('Install mongodb/laravel-mongodb and ext-mongodb to use MongoDB AI storage.');
        }

        return $driver;
    }

    /** @return class-string<Model> */
    public static function execution(): string
    {
        return self::driver() === 'mongodb' ? Models\MongoDB\Execution::class : Models\Execution::class;
    }
}
