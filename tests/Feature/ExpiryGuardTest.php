<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Exceptions\ShareNotAccessible;
use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Guards\NotExpiredGuard;
use RobinsonRyan\Mansio\Support\GuardResult;
use RobinsonRyan\Mansio\Support\ShareContext;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;

/**
 * R7 — an expired share is not accessible; NotExpiredGuard denies it.
 */
it('denies resolution of an expired share', function (): void {
    $doc = TestShareable::create(['title' => 'Expired']);
    Mansio::for($doc)->publishVersion('bytes');

    $share = Mansio::for($doc)->share(['expires_at' => now()->subMinute()]);

    expect(fn (): mixed => Mansio::resolve($share->token))
        ->toThrow(ShareNotAccessible::class);
});

it('allows resolution of a share expiring in the future', function (): void {
    $doc = TestShareable::create(['title' => 'Future']);
    Mansio::for($doc)->publishVersion('bytes');

    $share = Mansio::for($doc)->share(['expires_at' => now()->addDay()]);

    expect(Mansio::resolve($share->token)->id)->toBe($share->id);
});

it('NotExpiredGuard denies past-dated shares and passes others', function (): void {
    $doc = TestShareable::create(['title' => 'Guard']);
    $guard = new NotExpiredGuard;

    $expired = Mansio::for($doc)->share(['expires_at' => now()->subDay()]);
    $live = Mansio::for($doc)->share(['expires_at' => now()->addDay()]);

    expect($guard->check(new ShareContext($expired))->denied())->toBeTrue()
        ->and($guard->check(new ShareContext($live))->outcome)->toBe(GuardResult::PASS);
});
