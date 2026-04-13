<?php

declare(strict_types=1);

namespace JSDevArt\LaravelFileVault\Support;

final class ServeSignedRoutePolicy
{
    /**
     * Whether getUrl() should use the app temporary signed route instead of the disk presigned URL.
     */
    public static function shouldServeViaSignedRoute(string $diskDriver, bool $forceServeRoute): bool
    {
        return $diskDriver === 'local' || $forceServeRoute;
    }
}
