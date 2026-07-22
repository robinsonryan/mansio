<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Models\ShareEvent;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;

/**
 * R16 — the download route streams the serveable version's bytes with the correct
 * Content-Type and an attachment Content-Disposition, recording a downloaded event.
 */
it('streams the serveable version as an attachment with the right headers', function (): void {
    $doc = TestShareable::create(['title' => 'Gulf Stream Proposal']);
    Mansio::for($doc)->publishVersion('PDF-BYTES-HERE', ['mime' => 'application/pdf']);
    $share = Mansio::for($doc)->share();

    $response = $this->get('docs/' . $share->token . '/download');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect($response->streamedContent())->toBe('PDF-BYTES-HERE')
        ->and($response->headers->get('Content-Disposition'))
        ->toContain('attachment')
        ->toContain('.pdf');
})->group('http');

it('records a downloaded event on download', function (): void {
    $doc = TestShareable::create(['title' => 'Downloadable']);
    Mansio::for($doc)->publishVersion('bytes');
    $share = Mansio::for($doc)->share();

    $this->get('docs/' . $share->token . '/download')->assertOk();

    expect($share->events()->where('type', ShareEvent::DOWNLOADED)->exists())->toBeTrue();
})->group('http');

it('serves the latest bytes after a new version is published', function (): void {
    $doc = TestShareable::create(['title' => 'Swapped']);
    Mansio::for($doc)->publishVersion('OLD', ['mime' => 'text/plain']);
    $share = Mansio::for($doc)->share();

    Mansio::for($doc)->publishVersion('NEW', ['mime' => 'text/plain']);

    $response = $this->get('docs/' . $share->token . '/download');

    expect($response->streamedContent())->toBe('NEW');
})->group('http');
