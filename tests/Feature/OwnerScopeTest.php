<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Tests\Fixtures\OwnedShareable;
use RobinsonRyan\Mansio\Tests\Fixtures\TestOwner;

/**
 * R18 — optional owner scoping: when enabled, a share records the shareable's owner
 * morph (tenant/account) so consuming apps can scope by owner; when disabled, the
 * owner columns stay null regardless of what the shareable reports.
 */
it('records the owner morph on a share when owner scoping is enabled', function (): void {
    config(['mansio.owner.enabled' => true]);

    $owner = TestOwner::create(['name' => 'Acme Fleet']);
    $doc = OwnedShareable::create(['title' => 'Owned proposal']);
    $doc->ownerModel = $owner;

    $share = Mansio::for($doc)->share()->fresh();

    expect($share->owner_type)->toBe($owner->getMorphClass())
        ->and($share->owner_id)->toBe($owner->getKey());
});

it('leaves the owner columns null when owner scoping is disabled', function (): void {
    config(['mansio.owner.enabled' => false]);

    $owner = TestOwner::create(['name' => 'Acme Fleet']);
    $doc = OwnedShareable::create(['title' => 'Unscoped proposal']);
    $doc->ownerModel = $owner;

    $share = Mansio::for($doc)->share()->fresh();

    expect($share->owner_type)->toBeNull()
        ->and($share->owner_id)->toBeNull();
});
