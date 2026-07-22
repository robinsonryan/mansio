<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Exceptions\ShareNotAccessible;
use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Models\ShareEvent;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;

/**
 * R13 — revocation flips status/revoked_at, records an audit event, and makes the
 * share unresolvable.
 */
it('revokes a share and blocks further resolution', function (): void {
    $doc = TestShareable::create(['title' => 'Doomed deal']);
    Mansio::for($doc)->publishVersion('bytes');
    $share = Mansio::for($doc)->share();

    $revoked = Mansio::revoke($share);

    expect($revoked->status)->toBe('revoked')
        ->and($revoked->revoked_at)->not->toBeNull()
        ->and($revoked->isRevoked())->toBeTrue();

    expect(fn (): mixed => Mansio::resolve($share->token))
        ->toThrow(ShareNotAccessible::class);
});

it('writes a revoked audit event', function (): void {
    $doc = TestShareable::create(['title' => 'Audit revoke']);
    $share = Mansio::for($doc)->share();

    Mansio::revoke($share);

    expect($share->events()->where('type', ShareEvent::REVOKED)->exists())->toBeTrue();
});
