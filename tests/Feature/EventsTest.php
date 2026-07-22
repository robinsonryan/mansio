<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use RobinsonRyan\Mansio\Events\ShareAccessed;
use RobinsonRyan\Mansio\Events\ShareCreated;
use RobinsonRyan\Mansio\Events\ShareDownloaded;
use RobinsonRyan\Mansio\Events\ShareExpired;
use RobinsonRyan\Mansio\Events\ShareRevoked;
use RobinsonRyan\Mansio\Events\ShareUnlockFailed;
use RobinsonRyan\Mansio\Events\VersionPublished;
use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;

/**
 * R19 — domain events fire on each meaningful transition so consuming apps can hook
 * them (notifications, audit mirroring) without the package knowing those exist.
 * Only the asserted events are faked so model/DB events still run normally.
 */
it('dispatches VersionPublished on publish and on rollback', function (): void {
    Event::fake([VersionPublished::class]);

    $doc = TestShareable::create(['title' => 'Published']);

    Mansio::for($doc)->publishVersion('v1-bytes');
    Mansio::rollback($doc, toSequence: 1);

    Event::assertDispatched(VersionPublished::class, 2);
});

it('dispatches ShareCreated when a link is minted', function (): void {
    Event::fake([ShareCreated::class]);

    $doc = TestShareable::create(['title' => 'Minted']);
    $share = Mansio::for($doc)->share();

    Event::assertDispatched(
        ShareCreated::class,
        fn (ShareCreated $event): bool => $event->share->is($share),
    );
});

it('dispatches ShareAccessed when the landing page is viewed', function (): void {
    Event::fake([ShareAccessed::class]);

    $doc = TestShareable::create(['title' => 'Viewed']);
    Mansio::for($doc)->publishVersion('bytes');
    $share = Mansio::for($doc)->share();

    $this->get('docs/' . $share->token)->assertOk();

    Event::assertDispatched(
        ShareAccessed::class,
        fn (ShareAccessed $event): bool => $event->share->is($share),
    );
})->group('http');

it('dispatches ShareDownloaded when the artifact is downloaded', function (): void {
    Event::fake([ShareDownloaded::class]);

    $doc = TestShareable::create(['title' => 'Downloaded']);
    Mansio::for($doc)->publishVersion('bytes');
    $share = Mansio::for($doc)->share();

    $this->get('docs/' . $share->token . '/download')->assertOk();

    Event::assertDispatched(
        ShareDownloaded::class,
        fn (ShareDownloaded $event): bool => $event->share->is($share),
    );
})->group('http');

it('dispatches ShareRevoked when a link is revoked', function (): void {
    Event::fake([ShareRevoked::class]);

    $doc = TestShareable::create(['title' => 'Revoked']);
    $share = Mansio::for($doc)->share();

    Mansio::revoke($share);

    Event::assertDispatched(
        ShareRevoked::class,
        fn (ShareRevoked $event): bool => $event->share->is($share),
    );
});

it('dispatches ShareUnlockFailed on a wrong password over HTTP', function (): void {
    Event::fake([ShareUnlockFailed::class]);

    $doc = TestShareable::create(['title' => 'Locked']);
    Mansio::for($doc)->publishVersion('bytes');
    $share = Mansio::for($doc)->share(['password' => 'correct']);

    $this->post('docs/' . $share->token . '/unlock', ['password' => 'wrong'])->assertOk();

    Event::assertDispatched(
        ShareUnlockFailed::class,
        fn (ShareUnlockFailed $event): bool => $event->share->is($share) && $event->type === 'password',
    );
})->group('http');

it('dispatches ShareExpired once when an expired share is hit', function (): void {
    Event::fake([ShareExpired::class]);

    $doc = TestShareable::create(['title' => 'Expired']);
    Mansio::for($doc)->publishVersion('bytes');
    $share = Mansio::for($doc)->share(['expires_at' => now()->subMinute()]);

    // First hit trips the expiry transition; a second hit must not re-fire.
    $this->get('docs/' . $share->token)->assertNotFound();
    $this->get('docs/' . $share->token)->assertNotFound();

    Event::assertDispatched(
        ShareExpired::class,
        fn (ShareExpired $event): bool => $event->share->is($share),
    );
    Event::assertDispatchedTimes(ShareExpired::class, 1);
})->group('http');
