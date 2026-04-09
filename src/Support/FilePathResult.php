<?php

declare(strict_types=1);

namespace JSDevArt\LaravelFileVault\Support;

final class FilePathResult
{
    public function __construct(
        public readonly string $path,
        private readonly ?TemporaryFile $temp = null,
    ) {}
}
