<?php

declare(strict_types=1);

namespace YourVendor\LaravelFileVault\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use YourVendor\LaravelFileVault\Contracts\FileStorageInterface;

abstract class BaseFileStorageService implements FileStorageInterface
{
    protected string $disk;

    protected Filesystem $storage;

    public function __construct(protected string $context)
    {
        $this->disk    = config('file-vault.disk', config('filesystems.default'));
        $this->storage = Storage::disk($this->disk);
    }

    /**
     * Get the file contents from storage.
     */
    public function get(string $path): ?string
    {
        logger()->debug('[FileVault] get', [
            'path'    => $path,
            'context' => $this->context,
            'exists'  => $this->storage->exists($this->context.'/'.$path),
        ]);

        return $this->storage->get($this->context.'/'.$path);
    }

    /**
     * Check if the file exists in storage.
     */
    public function exists(string $path): bool
    {
        return $this->storage->exists($this->context.'/'.$path);
    }

    /**
     * Store a file and return the relative path (without context prefix).
     */
    public function store(string $element, string $filecontents, string $extension): string
    {
        $fullpath          = $this->buildPath($element, $extension);
        $path_with_context = $this->context.'/'.$fullpath;
        $stored            = $this->storage->put($path_with_context, $filecontents);

        logger()->debug('[FileVault] store', [
            'fullpath' => $fullpath,
            'success'  => $stored,
            'context'  => $this->context,
        ]);

        return $fullpath;
    }

    /**
     * Generate a temporary signed URL for the given path.
     */
    public function getUrl(string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $path_with_context = $this->context.'/'.$path;

        if (! $this->storage->exists($path_with_context)) {
            return null;
        }

        if ($this->disk === 'local') {
            return URL::temporarySignedRoute(
                config('file-vault.serve_route', 'files.serve'),
                now()->addMinutes($this->getUrlExpiryMinutes()),
                ['path' => base64_encode($path_with_context)]
            );
        }

        try {
            return $this->storage->temporaryUrl(
                $path_with_context,
                now()->addMinutes($this->getUrlExpiryMinutes())
            );
        } catch (\BadMethodCallException $e) {
            return $this->storage->url($path_with_context);
        }
    }

    /**
     * Delete a file from storage.
     */
    public function delete(string $path): bool
    {
        if (! $path) {
            return false;
        }

        $path_with_context = $this->context.'/'.$path;
        $deleted           = $this->storage->delete($path_with_context);

        logger()->debug('[FileVault] delete', [
            'path'    => $path,
            'success' => $deleted,
            'context' => $this->context,
        ]);

        return $deleted;
    }

    /**
     * Each concrete service defines how to build the base path for a given element.
     * Example: 'picture' → 'pictures', 'document' → 'documents'
     */
    abstract protected function buildBasePath(string $element): string;

    /* ── Private ─────────────────────────────────────────────────────────── */

    private function getUrlExpiryMinutes(): int
    {
        return (int) config('file-vault.expiry_minutes', 60);
    }

    /**
     * Builds a hierarchical path: element_path/XX/XX/uuid.extension
     * XX folders are random 2-char strings for uniform distribution.
     */
    private function buildPath(string $element, string $extension): string
    {
        $folder1   = $this->generateRandomFolderName();
        $folder2   = $this->generateRandomFolderName();
        $uuid      = Str::uuid()->toString();
        $cleanUuid = str_replace('-', '', strtolower($uuid));

        return $this->buildBasePath($element).'/'.$folder1.'/'.$folder2.'/'.$cleanUuid.'.'.$extension;
    }

    private function generateRandomFolderName(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        return substr(str_shuffle($chars), 0, 2);
    }
}
