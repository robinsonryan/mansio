<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;

/**
 * R20 — bad tokens return 404, never 403: an unknown, a revoked, and an expired token
 * are all indistinguishable from "never existed" over HTTP. We assert the concrete
 * status code (404) so a future switch to 403 can't slip through.
 */
it('returns 404 for an unknown token that never existed', function (): void {
    $response = $this->get('docs/' . str_repeat('z', 32));

    $response->assertNotFound();
    $response->assertStatus(404);
})->group('http');

it('returns 404 for a revoked share', function (): void {
    $doc = TestShareable::create(['title' => 'Revoked privacy']);
    Mansio::for($doc)->publishVersion('bytes');
    $share = Mansio::for($doc)->share();

    Mansio::revoke($share);

    $response = $this->get('docs/' . $share->token);

    $response->assertNotFound();
    $response->assertStatus(404);
})->group('http');

it('returns 404 for an expired share', function (): void {
    $doc = TestShareable::create(['title' => 'Expired privacy']);
    Mansio::for($doc)->publishVersion('bytes');
    $share = Mansio::for($doc)->share(['expires_at' => now()->subMinute()]);

    $response = $this->get('docs/' . $share->token);

    $response->assertNotFound();
    $response->assertStatus(404);
})->group('http');
