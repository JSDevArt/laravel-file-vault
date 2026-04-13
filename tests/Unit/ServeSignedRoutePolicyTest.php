<?php

declare(strict_types=1);

use JSDevArt\LaravelFileVault\Support\ServeSignedRoutePolicy;

test('local driver always uses signed app route', function () {
    expect(ServeSignedRoutePolicy::shouldServeViaSignedRoute('local', false))->toBeTrue()
        ->and(ServeSignedRoutePolicy::shouldServeViaSignedRoute('local', true))->toBeTrue();
});

test('non-local driver uses presigned path unless force is enabled', function () {
    expect(ServeSignedRoutePolicy::shouldServeViaSignedRoute('s3', false))->toBeFalse()
        ->and(ServeSignedRoutePolicy::shouldServeViaSignedRoute('s3', true))->toBeTrue()
        ->and(ServeSignedRoutePolicy::shouldServeViaSignedRoute('custom', false))->toBeFalse()
        ->and(ServeSignedRoutePolicy::shouldServeViaSignedRoute('custom', true))->toBeTrue();
});
