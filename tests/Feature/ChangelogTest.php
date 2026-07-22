<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;

/**
 * R15 — the landing page exposes a changelog generated from version history
 * (sequence + summary + published_at), newest first.
 */
it('renders a changelog from version history, newest first', function (): void {
    $doc = TestShareable::create(['title' => 'Changelog doc']);
    Mansio::for($doc)->publishVersion('v1', ['summary' => 'Initial draft']);
    Mansio::for($doc)->publishVersion('v2', ['summary' => 'Corrected transport totals']);
    $share = Mansio::for($doc)->share();

    $response = $this->get('docs/' . $share->token);

    $response->assertOk()
        ->assertSee('Initial draft')
        ->assertSee('Corrected transport totals')
        // Newest sequence appears before the older one.
        ->assertSeeInOrder(['v2', 'v1']);
})->group('http');
