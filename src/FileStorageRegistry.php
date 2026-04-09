<?php

declare(strict_types=1);

namespace JSDevArt\LaravelFileVault;

use InvalidArgumentException;
use JSDevArt\LaravelFileVault\Services\BaseFileStorageService;

class FileStorageRegistry
{
    /** @var array<string, BaseFileStorageService> */
    private array $services = [];

    /**
     * Register a storage service instance under the given name.
     *
     * Call this from your AppServiceProvider::boot() method:
     *
     *   app(FileStorageRegistry::class)->register(
     *       'imports',
     *       new ImportStorageService(disk: 's3', context: 'imports'),
     *   );
     */
    public function register(string $name, BaseFileStorageService $service): void
    {
        $this->services[$name] = $service;
    }

    /**
     * Returns true if a service has been registered under the given name.
     */
    public function has(string $name): bool
    {
        return isset($this->services[$name]);
    }

    /**
     * Resolve the storage service registered under the given name.
     *
     * @throws InvalidArgumentException if no service has been registered for $name.
     */
    public function get(string $name): BaseFileStorageService
    {
        if (! isset($this->services[$name])) {
            throw new InvalidArgumentException(
                "No file storage service registered with name [{$name}]. ".
                'Register one via app(FileStorageRegistry::class)->register() in your AppServiceProvider.'
            );
        }

        return $this->services[$name];
    }
}
