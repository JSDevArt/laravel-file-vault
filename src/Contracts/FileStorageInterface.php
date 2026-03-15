<?php

declare(strict_types=1);

namespace YourVendor\LaravelFileVault\Contracts;

interface FileStorageInterface
{
    public function get(string $path): ?string;

    public function store(string $element, string $filecontents, string $extension): string;

    public function getUrl(string $path): ?string;

    public function delete(string $path): bool;

    public function exists(string $path): bool;
}
