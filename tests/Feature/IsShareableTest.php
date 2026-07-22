<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Contracts\Shareable;
use RobinsonRyan\Mansio\Models\Share;
use RobinsonRyan\Mansio\Models\Version;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;

/**
 * R1 — any Eloquent model becomes shareable by implementing the contract and using
 * the trait; the package reaches back into no consumer class.
 */
it('makes a plain model shareable via the contract and trait', function (): void {
    $doc = TestShareable::create(['title' => 'Gulf Stream 4x4']);

    expect($doc)->toBeInstanceOf(Shareable::class)
        ->and($doc->mansioDefaultMime())->toBe('application/pdf')
        ->and($doc->mansioOwner())->toBeNull();
});

it('publishes versions and mints shares through the trait methods', function (): void {
    $doc = TestShareable::create(['title' => 'Proposal']);

    $version = $doc->publishVersion('BYTES', ['summary' => 'draft']);
    $share = $doc->share(['label' => 'Bob']);

    expect($version)->toBeInstanceOf(Version::class)
        ->and($share)->toBeInstanceOf(Share::class);
});

it('exposes version history newest sequence first and its shares', function (): void {
    $doc = TestShareable::create(['title' => 'History']);

    $doc->publishVersion('v1');
    $doc->publishVersion('v2');
    $doc->publishVersion('v3');

    $doc->share();
    $doc->share();

    expect($doc->mansioVersions()->pluck('sequence')->all())->toBe([3, 2, 1])
        ->and($doc->mansioShares()->count())->toBe(2);
});
