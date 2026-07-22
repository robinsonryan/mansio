<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Actions\RecordAccess;
use RobinsonRyan\Mansio\Exceptions\ShareNotAccessible;
use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Models\ShareEvent;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;

/**
 * R9 — max_views is enforced: the (max_views + 1)th view is denied.
 */
it('allows up to max_views then denies the next', function (): void {
    $doc = TestShareable::create(['title' => 'Limited']);
    Mansio::for($doc)->publishVersion('bytes');

    $share = Mansio::for($doc)->share(['max_views' => 2]);
    $record = app(RecordAccess::class);

    // View 1 — allowed, then counted.
    Mansio::resolve($share->token);
    $record->handle($share->fresh(), ShareEvent::VIEWED);

    // View 2 — allowed, then counted.
    Mansio::resolve($share->fresh()->token);
    $record->handle($share->fresh(), ShareEvent::VIEWED);

    // View 3 — over the limit.
    expect(fn (): mixed => Mansio::resolve($share->fresh()->token))
        ->toThrow(ShareNotAccessible::class);

    expect($share->fresh()->view_count)->toBe(2);
});

it('enforces the limit end-to-end over HTTP', function (): void {
    $doc = TestShareable::create(['title' => 'HTTP Limited']);
    Mansio::for($doc)->publishVersion('bytes');
    $share = Mansio::for($doc)->share(['max_views' => 1]);

    $this->get('docs/' . $share->token)->assertOk();
    $this->get('docs/' . $share->token)->assertNotFound();
})->group('http');
