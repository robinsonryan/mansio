<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Models\Version;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;

/**
 * R2 — publishing writes an immutable version row carrying full metadata
 * (mime, size, sha256 checksum, source ref, summary, sequence, published_at).
 */
it('records complete metadata for a published version', function (): void {
    $doc = TestShareable::create(['title' => 'Spec sheet']);
    $bytes = 'PDF-CONTENT-BYTES';

    $version = Mansio::for($doc)->publishVersion($bytes, [
        'mime' => 'application/pdf',
        'source_ref' => 'abc1234',
        'summary' => 'Corrected §5.1 transport totals',
    ]);

    expect($version->sequence)->toBe(1)
        ->and($version->mime)->toBe('application/pdf')
        ->and($version->size_bytes)->toBe(strlen($bytes))
        ->and($version->checksum)->toBe(hash('sha256', $bytes))
        ->and($version->source_ref)->toBe('abc1234')
        ->and($version->summary)->toBe('Corrected §5.1 transport totals')
        ->and($version->published_at)->not->toBeNull()
        ->and($version->shareable_type)->toBe($doc->getMorphClass())
        ->and($version->shareable_id)->toBe($doc->getKey());
});

it('falls back to the shareable default mime when none is given', function (): void {
    $doc = TestShareable::create(['title' => 'No mime']);

    $version = Mansio::for($doc)->publishVersion('bytes');

    expect($version->mime)->toBe($doc->mansioDefaultMime());
});

it('increments the sequence per shareable', function (): void {
    $a = TestShareable::create(['title' => 'A']);
    $b = TestShareable::create(['title' => 'B']);

    $a1 = Mansio::for($a)->publishVersion('a1');
    $a2 = Mansio::for($a)->publishVersion('a2');
    $b1 = Mansio::for($b)->publishVersion('b1');

    expect($a1->sequence)->toBe(1)
        ->and($a2->sequence)->toBe(2)
        ->and($b1->sequence)->toBe(1);
});

it('keeps versions immutable — no updated_at column', function (): void {
    expect(Version::UPDATED_AT)->toBeNull();
});
