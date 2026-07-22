<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Models\ShareEvent;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;

/**
 * R14 — every access is audited (type, IP, user agent, timestamp) and the view
 * counter increments correctly.
 */
it('records a viewed event with IP and user agent, bumping view_count', function (): void {
    $doc = TestShareable::create(['title' => 'Audited']);
    Mansio::for($doc)->publishVersion('bytes');
    $share = Mansio::for($doc)->share();

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->get('docs/' . $share->token)
        ->assertOk();

    $event = $share->events()->where('type', ShareEvent::VIEWED)->first();

    expect($event)->not->toBeNull()
        ->and($event->ip)->toBe('203.0.113.9')
        ->and($event->user_agent)->not->toBeNull()
        ->and($event->created_at)->not->toBeNull()
        ->and($share->fresh()->view_count)->toBe(1);
})->group('http');

it('records a downloaded event', function (): void {
    $doc = TestShareable::create(['title' => 'Audited download']);
    Mansio::for($doc)->publishVersion('bytes');
    $share = Mansio::for($doc)->share();

    $this->get('docs/' . $share->token . '/download')->assertOk();

    expect($share->events()->where('type', ShareEvent::DOWNLOADED)->exists())->toBeTrue();
})->group('http');

it('records an unlock_attempt event on a failed unlock', function (): void {
    $doc = TestShareable::create(['title' => 'Audited unlock']);
    Mansio::for($doc)->publishVersion('bytes');
    $share = Mansio::for($doc)->share(['password' => 'right']);

    $this->post('docs/' . $share->token . '/unlock', ['password' => 'wrong'])->assertOk();

    expect($share->events()->where('type', ShareEvent::UNLOCK_ATTEMPT)->exists())->toBeTrue();
})->group('http');

it('increments view_count once per view', function (): void {
    $doc = TestShareable::create(['title' => 'Counter']);
    Mansio::for($doc)->publishVersion('bytes');
    $share = Mansio::for($doc)->share();

    $this->get('docs/' . $share->token)->assertOk();
    $this->get('docs/' . $share->token)->assertOk();
    $this->get('docs/' . $share->token)->assertOk();

    expect($share->fresh()->view_count)->toBe(3)
        ->and($share->events()->where('type', ShareEvent::VIEWED)->count())->toBe(3);
})->group('http');
