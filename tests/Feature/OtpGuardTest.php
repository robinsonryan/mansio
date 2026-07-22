<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use RobinsonRyan\Mansio\Exceptions\ShareNotAccessible;
use RobinsonRyan\Mansio\Exceptions\UnlockRequired;
use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Guards\EmailOtpGuard;
use RobinsonRyan\Mansio\Models\Share;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;

/**
 * R11 — the opt-in EmailOtpGuard: when a share requires OTP and the guard is in the
 * pipeline, a missing code challenges, a wrong code denies, the cached code passes.
 */
function otpShare(): Share
{
    $doc = TestShareable::create(['title' => 'Verify you']);
    Mansio::for($doc)->publishVersion('bytes');

    return Mansio::for($doc)->share([
        'settings' => [
            'otp' => true,
            'guards' => [EmailOtpGuard::class],
        ],
    ]);
}

it('challenges when no OTP is supplied', function (): void {
    $share = otpShare();

    expect(fn (): mixed => Mansio::resolve($share->token))
        ->toThrow(UnlockRequired::class);
});

it('denies a wrong OTP', function (): void {
    $share = otpShare();
    Cache::put('mansio_otp.' . $share->id, '123456');

    expect(fn (): mixed => Mansio::resolve($share->token, ['credentials' => ['otp' => '000000']]))
        ->toThrow(ShareNotAccessible::class);
});

it('passes with the matching cached OTP', function (): void {
    $share = otpShare();
    Cache::put('mansio_otp.' . $share->id, '654321');

    $resolved = Mansio::resolve($share->token, ['credentials' => ['otp' => '654321']]);

    expect($resolved->id)->toBe($share->id);
});

it('passes freely when the share does not require OTP', function (): void {
    $doc = TestShareable::create(['title' => 'No OTP']);
    $share = Mansio::for($doc)->share(['settings' => ['guards' => [EmailOtpGuard::class]]]);

    expect(Mansio::resolve($share->token)->id)->toBe($share->id);
});
