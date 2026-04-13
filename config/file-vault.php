<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk
    |--------------------------------------------------------------------------
    | The disk to use for storing files. If null, falls back to the
    | application's default filesystem disk (filesystems.default).
    |
    | Supported: "local", "s3", or any disk defined in filesystems.php
    */
    'disk' => env('FILE_VAULT_DISK', null),

    /*
    |--------------------------------------------------------------------------
    | Force Signed App Route for getUrl()
    |--------------------------------------------------------------------------
    | When true, getUrl() always uses the Laravel temporary signed route
    | (file-vault.serve_route) instead of the disk's presigned URL. Use this
    | when the storage endpoint is not reachable from browsers (e.g. internal
    | S3/MinIO URL in Docker) and your app should proxy downloads.
    */
    'force_serve_route' => env('FILE_VAULT_FORCE_SERVE_ROUTE', false),

    /*
    |--------------------------------------------------------------------------
    | Signed URL Expiry
    |--------------------------------------------------------------------------
    | Minutes before a generated URL expires: app signed routes (local disk or
    | force_serve_route) use this TTL; native S3 presigned URLs use the same
    | value when presigning.
    */
    'expiry_minutes' => env('FILE_VAULT_EXPIRY_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Local Disk Serving Route Name
    |--------------------------------------------------------------------------
    | When using the local disk (or when force_serve_route is true), getUrl()
    | generates a Laravel signed URL.
    | This must match the name of the route you define in your application
    | to serve private files (the route that validates the signature and
    | streams the file back to the browser).
    |
    | See README.md for a reference implementation.
    */
    'serve_route' => env('FILE_VAULT_SERVE_ROUTE', 'files.serve'),
];
