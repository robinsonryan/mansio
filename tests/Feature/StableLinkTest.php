<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;

/**
 * R4 — a share is a stable link: it serves the shareable's latest version, and
 * publishing a new version swaps what the SAME token serves with no token change.
 */
it('serves the latest version and swaps content without changing the token', function (): void {
    $doc = TestShareable::create(['title' => 'Stable']);

    $v1 = Mansio::for($doc)->publishVersion('v1-bytes');
    $share = Mansio::for($doc)->share();
    $token = $share->token;

    expect($share->serveableVersion()->id)->toBe($v1->id);

    $v2 = Mansio::for($doc)->publishVersion('v2-bytes');

    $reloaded = $share->fresh();

    expect($reloaded->token)->toBe($token)
        ->and($reloaded->serveableVersion()->id)->toBe($v2->id);
});

it('updates every outstanding link to the same shareable', function (): void {
    $doc = TestShareable::create(['title' => 'Many links']);
    Mansio::for($doc)->publishVersion('v1');

    $a = Mansio::for($doc)->share();
    $b = Mansio::for($doc)->share();

    $v2 = Mansio::for($doc)->publishVersion('v2');

    expect($a->fresh()->serveableVersion()->id)->toBe($v2->id)
        ->and($b->fresh()->serveableVersion()->id)->toBe($v2->id);
});
