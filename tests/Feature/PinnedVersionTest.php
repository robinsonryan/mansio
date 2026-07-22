<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;

/**
 * R5 — a pinned share keeps serving its pinned version even after newer versions
 * are published.
 */
it('serves the pinned version regardless of newer publishes', function (): void {
    $doc = TestShareable::create(['title' => 'Pinned']);

    $v1 = Mansio::for($doc)->publishVersion('v1');
    $v2 = Mansio::for($doc)->publishVersion('v2');

    $share = Mansio::for($doc)->share(['pinned_version_id' => $v1->id]);

    expect($share->serveableVersion()->id)->toBe($v1->id);

    Mansio::for($doc)->publishVersion('v3');

    expect($share->fresh()->serveableVersion()->id)->toBe($v1->id);
});

it('serves latest when not pinned, for contrast', function (): void {
    $doc = TestShareable::create(['title' => 'Unpinned']);

    Mansio::for($doc)->publishVersion('v1');
    $v2 = Mansio::for($doc)->publishVersion('v2');

    $share = Mansio::for($doc)->share();

    expect($share->serveableVersion()->id)->toBe($v2->id);
});
