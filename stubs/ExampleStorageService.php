<?php

declare(strict_types=1);

/**
 * Template: copy into your application (e.g. app/Services/Storage/UserStorageService.php),
 * rename the class, and fix the namespace to match your app.
 */

namespace App\Services\Storage;

use JSDevArt\LaravelFileVault\Services\BaseFileStorageService;

class ExampleStorageService extends BaseFileStorageService
{
    public function __construct()
    {
        parent::__construct('example');
    }

    protected function buildBasePath(string $element): string
    {
        return match ($element) {
            'picture' => 'pictures',
            'document' => 'documents',
            default => $element,
        };
    }
}
