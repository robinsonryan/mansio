<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Exceptions\ShareNotAccessible;
use RobinsonRyan\Mansio\Exceptions\UnlockRequired;
use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Models\Share;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;

/**
 * R8 — password protection: no credential challenges, a wrong one denies, the right
 * one passes; the HTTP unlock route throttles repeated wrong attempts.
 */
function passwordShare(string $password = 'letmein'): Share
{
    $doc = TestShareable::create(['title' => 'Locked']);
    Mansio::for($doc)->publishVersion('bytes');

    return Mansio::for($doc)->share(['password' => $password]);
}

it('challenges when no password is supplied', function (): void {
    $share = passwordShare();

    expect(fn (): mixed => Mansio::resolve($share->token))
        ->toThrow(UnlockRequired::class);
});

it('denies a wrong password', function (): void {
    $share = passwordShare();

    expect(fn (): mixed => Mansio::resolve($share->token, ['credentials' => ['password' => 'nope']]))
        ->toThrow(ShareNotAccessible::class);
});

it('passes with the correct password', function (): void {
    $share = passwordShare('s3cret');

    $resolved = Mansio::resolve($share->token, ['credentials' => ['password' => 's3cret']]);

    expect($resolved->id)->toBe($share->id);
});

it('throttles repeated wrong unlock attempts on the HTTP route', function (): void {
    $share = passwordShare('correct-horse');

    $max = (int) config('mansio.password.throttle.max_attempts', 5);

    // Exhaust the allowed wrong attempts.
    for ($i = 0; $i < $max; $i++) {
        $this->post('docs/' . $share->token . '/unlock', ['password' => 'wrong'])
            ->assertOk();
    }

    // The next attempt is rate-limited.
    $this->post('docs/' . $share->token . '/unlock', ['password' => 'wrong'])
        ->assertOk()
        ->assertSee('Too many attempts', false);
})->group('http');

it('unlocks over HTTP with the correct password and remembers it in session', function (): void {
    $share = passwordShare('open-sesame');

    $this->post('docs/' . $share->token . '/unlock', ['password' => 'open-sesame'])
        ->assertRedirect(route('mansio.show', $share->token));

    // Session now holds the unlock, so the landing page renders.
    $this->get('docs/' . $share->token)->assertOk();
})->group('http');
