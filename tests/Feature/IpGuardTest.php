<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Exceptions\ShareNotAccessible;
use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Guards\IpAllowGuard;
use RobinsonRyan\Mansio\Models\Share;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;

/**
 * R12 — the opt-in IpAllowGuard: a request IP outside the allow-list is denied,
 * one inside passes.
 */
function ipShare(): Share
{
    $doc = TestShareable::create(['title' => 'IP gated']);
    Mansio::for($doc)->publishVersion('bytes');

    return Mansio::for($doc)->share([
        'settings' => [
            'allowed_ips' => ['10.0.0.5', '10.0.0.6'],
            'guards' => [IpAllowGuard::class],
        ],
    ]);
}

it('denies a request IP that is not in the allow-list', function (): void {
    $share = ipShare();

    expect(fn (): mixed => Mansio::resolve($share->token, ['ip' => '8.8.8.8']))
        ->toThrow(ShareNotAccessible::class);
});

it('passes a request IP that is in the allow-list', function (): void {
    $share = ipShare();

    expect(Mansio::resolve($share->token, ['ip' => '10.0.0.6'])->id)->toBe($share->id);
});

it('passes freely when no allow-list is configured', function (): void {
    $doc = TestShareable::create(['title' => 'Open IP']);
    $share = Mansio::for($doc)->share(['settings' => ['guards' => [IpAllowGuard::class]]]);

    expect(Mansio::resolve($share->token, ['ip' => '8.8.8.8'])->id)->toBe($share->id);
});
