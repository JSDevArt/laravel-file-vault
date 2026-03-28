<?php

declare(strict_types=1);

namespace JSDevArt\LaravelFileVault;

use Illuminate\Support\ServiceProvider;

class FileVaultServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/file-vault.php',
            'file-vault'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/file-vault.php' => config_path('file-vault.php'),
        ], 'file-vault-config');
    }
}
