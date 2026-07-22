<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Models\Share;
use RobinsonRyan\Mansio\Models\Version;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;

it('publishes a version, mints a share, and serves the latest version', function (): void {
    $doc = TestShareable::create(['title' => 'Gulf Stream 4x4']);

    // R1/R2 — a model becomes shareable and publishes an immutable version.
    $v1 = Mansio::for($doc)->publishVersion('PDF-BYTES-V1', [
        'mime' => 'application/pdf',
        'summary' => 'Initial draft',
    ]);

    expect($v1)->toBeInstanceOf(Version::class)
        ->and($v1->id)->not->toBeNull()
        ->and($v1->sequence)->toBe(1)
        ->and($v1->checksum)->toBe(hash('sha256', 'PDF-BYTES-V1'));

    // R3 — mint a public link.
    $share = Mansio::for($doc)->share(['label' => 'Gulf Stream — Bob']);

    expect($share)->toBeInstanceOf(Share::class)
        ->and($share->token)->toHaveLength(32)
        ->and($share->serveableVersion()->id)->toBe($v1->id);

    // R4 — publishing a new version updates the same link with no link change.
    $token = $share->token;
    $v2 = Mansio::for($doc)->publishVersion('PDF-BYTES-V2', ['summary' => 'Corrected §5.1']);

    expect($v2->sequence)->toBe(2)
        ->and($share->fresh()->token)->toBe($token)
        ->and($share->fresh()->serveableVersion()->id)->toBe($v2->id);
});
