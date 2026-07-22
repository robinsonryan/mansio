<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;

/**
 * R10 — a one-time share burns after its first successful view.
 */
it('burns a one-time share after the first view', function (): void {
    $doc = TestShareable::create(['title' => 'Burn']);
    Mansio::for($doc)->publishVersion('bytes');
    $share = Mansio::for($doc)->share(['one_time' => true]);

    // First view succeeds and increments the counter.
    $this->get('docs/' . $share->token)->assertOk();

    expect($share->fresh()->view_count)->toBe(1);

    // Second view is denied — the link is consumed.
    $this->get('docs/' . $share->token)->assertNotFound();
})->group('http');
