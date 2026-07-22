<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;

/**
 * R3 — minting a share generates a random 32-char unguessable token and persists the
 * requested access options; a password is stored hashed, never in the clear.
 */
it('generates a unique 32-character token per share', function (): void {
    $doc = TestShareable::create(['title' => 'Tokens']);

    $tokens = collect(range(1, 10))
        ->map(fn (): string => Mansio::for($doc)->share()->token);

    $tokens->each(function (string $token): void {
        expect($token)->toHaveLength(32)
            ->and($token)->toMatch('/^[0-9A-Za-z]{32}$/');
    });

    expect($tokens->unique()->count())->toBe(10);
});

it('persists the requested share options', function (): void {
    $doc = TestShareable::create(['title' => 'Options']);
    $pinned = Mansio::for($doc)->publishVersion('bytes');
    $expiry = now()->addDays(30)->startOfSecond();

    $share = Mansio::for($doc)->share([
        'label' => 'Gulf Stream — Bob',
        'expires_at' => $expiry,
        'max_views' => 3,
        'one_time' => true,
        'pinned_version_id' => $pinned->id,
    ])->fresh();

    expect($share->label)->toBe('Gulf Stream — Bob')
        ->and($share->expires_at->equalTo($expiry))->toBeTrue()
        ->and($share->max_views)->toBe(3)
        ->and($share->one_time)->toBeTrue()
        ->and($share->pinned_version_id)->toBe($pinned->id)
        ->and($share->status)->toBe('active')
        ->and($share->view_count)->toBe(0);
});

it('stores the password hashed, not in plaintext', function (): void {
    $doc = TestShareable::create(['title' => 'Secret']);

    $share = Mansio::for($doc)->share(['password' => 'hunter2']);

    expect($share->password_hash)->not->toBeNull()
        ->and($share->password_hash)->not->toBe('hunter2')
        ->and(Hash::check('hunter2', $share->password_hash))->toBeTrue();
});

it('leaves password_hash null when no password is requested', function (): void {
    $doc = TestShareable::create(['title' => 'Open']);

    expect(Mansio::for($doc)->share()->password_hash)->toBeNull();
});
