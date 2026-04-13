# Laravel File Vault

Private file storage for Laravel with signed URLs, multi-context organization, and local/S3 support.

## Requirements

- PHP 8.2+ for Laravel 11–12; **PHP 8.4+** when you use Laravel 13 (Symfony 8 components require it)
- Laravel 11, 12, or 13

## Installation

```bash
composer require jsdevart/laravel-file-vault
```

The package registers itself automatically via Laravel's package discovery.

Publish the config file:

```bash
php artisan vendor:publish --tag=file-vault-config
```

## Configuration

`config/file-vault.php`:

| Key | Default | Description |
|---|---|---|
| `disk` | `null` (uses `filesystems.default`) | Storage disk to use (`local`, `s3`, etc.) |
| `expiry_minutes` | `60` | Signed URL expiry time in minutes |
| `force_serve_route` | `false` | If `true`, `getUrl()` always uses the app signed route (see below) instead of S3 presigned URLs |
| `serve_route` | `files.serve` | Route name that serves private files when using the signed route |

Environment variables:

```env
FILE_VAULT_DISK=local
FILE_VAULT_EXPIRY_MINUTES=60
FILE_VAULT_SERVE_ROUTE=files.serve
FILE_VAULT_FORCE_SERVE_ROUTE=false
```

Set `FILE_VAULT_FORCE_SERVE_ROUTE=true` when your storage disk uses an **internal** endpoint (for example MinIO or S3 at a Docker-only hostname). Presigned URLs would point at that host and fail in the browser; forcing the signed route makes `getUrl()` return an URL on your app, which can stream the file via `Storage` in your serve controller.

## Usage

### 1. Create a storage service for your context

Copy `stubs/ExampleStorageService.php` into your application and adjust it:

```php
// app/Services/Storage/UserStorageService.php

namespace App\Services\Storage;

use JSDevArt\LaravelFileVault\Services\BaseFileStorageService;

class UserStorageService extends BaseFileStorageService
{
    public function __construct()
    {
        parent::__construct('user'); // root folder in storage
    }

    protected function buildBasePath(string $element): string
    {
        return match ($element) {
            'picture'  => 'pictures',
            'document' => 'documents',
            default    => $element,
        };
    }
}
```

Files will be stored at: `{context}/{element_folder}/XX/XX/{uuid}.{ext}`

Example: `user/pictures/aB/cD/3f2a...uuid.jpg`

### 2. Register your services

In your `AppServiceProvider`:

```php
use JSDevArt\LaravelFileVault\FileStorageRegistry;
use App\Services\Storage\UserStorageService;

public function boot(): void
{
    app(FileStorageRegistry::class)->register(
        'user',
        new UserStorageService(),
    );
}
```

You can also pass a custom disk per registration, without creating a subclass:

```php
// Uses the named-argument form of the parent constructor
app(FileStorageRegistry::class)->register(
    'imports',
    new ImportStorageService(disk: 's3', context: 'imports'),
);
```

### 3. Use in your controllers

```php
use JSDevArt\LaravelFileVault\FileStorageRegistry;

// Store a file
$service = app(FileStorageRegistry::class)->get('user');
$path = $service->store('picture', $request->file('photo')->get(), 'jpg');
$user->update(['photo_path' => $path]);

// Generate a signed URL (expires per config)
$url = app(FileStorageRegistry::class)->get('user')->getUrl($user->photo_path);

// Delete a file
app(FileStorageRegistry::class)->get('user')->delete($user->photo_path);

// Check existence
app(FileStorageRegistry::class)->get('user')->exists($user->photo_path);
```

### 4. Display the URL in a view or API response

```php
// In a model accessor (recommended)
public function getPhotoUrlAttribute(): ?string
{
    return $this->photo_path
        ? app(FileStorageRegistry::class)->get('user')->getUrl($this->photo_path)
        : null;
}
```

### 5. Get an absolute on-disk path (`getPath`)

Some libraries (e.g. `spatie/simple-excel`, PhpSpreadsheet) require a real file-system path rather than file contents. Use `getPath()` to get one regardless of where the file lives:

```php
use JSDevArt\LaravelFileVault\FileStorageRegistry;

$storage = app(FileStorageRegistry::class)->get('imports');

// Returns FilePathResult|null  (null when the file does not exist)
$result = $storage->getPath($filePath);

if ($result === null) {
    abort(404);
}

// Keep $result in scope for as long as you need $result->path.
// If the file lives on a remote disk the package created a local temp file;
// it is deleted automatically when $result goes out of scope.
$rows = SimpleExcelReader::create($result->path)
    ->headersToSnakeCase()
    ->getRows()
    ->collect();
```

**How it works:**

| Disk driver | Behaviour |
|---|---|
| `local` | Returns the absolute path directly via `Storage::path()` — no copy is made. |
| Remote (S3, GCS, …) | Downloads the file to a temp file in `sys_get_temp_dir()`. The original file extension is preserved so format-detection (`.xlsx`, `.csv`, …) works correctly. |

**Why `FilePathResult` instead of a plain string?**

The temp file's lifetime is tied to the `FilePathResult` object. If you extracted just the path string — `$path = $storage->getPath($file)->path` — the result object would be immediately garbage-collected, deleting the temp file before you use it. Keeping the full object in a named variable prevents that.

```php
// Correct — $result stays alive while $result->path is used
$result = $storage->getPath($file);
process($result->path);

// Incorrect — temp file deleted before process() runs
process($storage->getPath($file)->path);
```

## Serving private files (local disk)

The package does **not** provide a controller. You are responsible for implementing the route that streams private files. This gives you full control over middleware, authorization, and response headers.

The example below uses the same disk as File Vault (`file-vault.disk` or your default filesystem disk). It assumes a **local** (or otherwise path-based) disk, because `Storage::disk(...)->path()` is not meaningful for pure S3-style drivers.

Reference implementation:

```php
// routes/web.php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/files/{path}', function (Request $request, string $path) {
    if (! $request->hasValidSignature()) {
        abort(403);
    }

    $disk    = config('file-vault.disk') ?? config('filesystems.default');
    $decoded = base64_decode($path);

    if (! $decoded || ! Storage::disk($disk)->exists($decoded)) {
        abort(404);
    }

    $filePath = Storage::disk($disk)->path($decoded);
    $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

    return response()->stream(function () use ($filePath) {
        $stream = fopen($filePath, 'rb');
        fpassthru($stream);
        fclose($stream);
    }, 200, [
        'Content-Type'        => $mimeType,
        'Content-Length'      => Storage::disk($disk)->size($decoded),
        'Cache-Control'       => 'private, max-age=3600',
        'Content-Disposition' => 'inline; filename="'.basename($decoded).'"',
    ]);
})->name('files.serve')->where('path', '.*');
```

Make sure the route name matches `file-vault.serve_route` in your config (default: `files.serve`).

For **S3** (and other cloud disks), `getUrl()` normally delegates to the driver's native `temporaryUrl()`. With `force_serve_route` enabled, it uses the same signed app route as the local driver so you can proxy the object through Laravel.