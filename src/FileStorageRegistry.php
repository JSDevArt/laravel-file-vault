<?php

declare(strict_types=1);

namespace YourVendor\LaravelFileVault;

use InvalidArgumentException;
use YourVendor\LaravelFileVault\Contracts\FileStorageInterface;

final class FileStorageRegistry
{
    /** @var array<string, class-string<FileStorageInterface>> */
    private static array $bindings = [];

    /** @var array<string, FileStorageInterface> */
    private static array $instances = [];

    /**
     * Register a storage service class for a given context key.
     *
     * Call this from your AppServiceProvider::register() method:
     *
     *   FileStorageRegistry::register('user', UserStorageService::class);
     */
    public static function register(string $key, string $serviceClass): void
    {
        self::$bindings[$key] = $serviceClass;
        unset(self::$instances[$key]); // invalidate cached instance if re-registered
    }

    /**
     * Resolve the storage service for the given context key.
     */
    public static function get(string $key): FileStorageInterface
    {
        if (! isset(self::$instances[$key])) {
            self::$instances[$key] = self::createService($key);
        }

        return self::$instances[$key];
    }

    /**
     * Returns all registered context keys.
     *
     * @return string[]
     */
    public static function keys(): array
    {
        return array_keys(self::$bindings);
    }

    /**
     * Clears all bindings and instances (useful for testing).
     */
    public static function flush(): void
    {
        self::$bindings  = [];
        self::$instances = [];
    }

    private static function createService(string $key): FileStorageInterface
    {
        if (! isset(self::$bindings[$key])) {
            throw new InvalidArgumentException(
                "No storage service registered for context [{$key}]. ".
                'Register one via FileStorageRegistry::register() in your AppServiceProvider.'
            );
        }

        return new (self::$bindings[$key])();
    }
}
