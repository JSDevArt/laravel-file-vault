<?php

declare(strict_types=1);

namespace JSDevArt\LaravelFileVault\Support;

final class TemporaryFile
{
    public readonly string $path;

    public function __construct(string $extension, string $contents)
    {
        $base = tempnam(sys_get_temp_dir(), 'file_vault_');
        unlink($base);

        $this->path = $base.($extension !== '' ? '.'.$extension : '');
        file_put_contents($this->path, $contents);
    }

    public function __destruct()
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }
}
