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
    | Signed URL Expiry
    |--------------------------------------------------------------------------
    | Minutes before a generated signed URL expires. Only applies to local
    | disk. S3 uses its own presigned URL TTL (same value is used).
    */
    'expiry_minutes' => env('FILE_VAULT_EXPIRY_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Local Disk Serving Route Name
    |--------------------------------------------------------------------------
    | When using the local disk, getUrl() generates a Laravel signed URL.
    | This must match the name of the route you define in your application
    | to serve private files (the route that validates the signature and
    | streams the file back to the browser).
    |
    | See README.md for a reference implementation.
    */
    'serve_route' => env('FILE_VAULT_SERVE_ROUTE', 'files.serve'),
];
