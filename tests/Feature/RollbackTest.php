<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;

/**
 * R6 — rollback repoints "current" to earlier content by appending a new latest
 * version whose bytes/checksum match the target sequence. History stays append-only.
 */
it('rolls current forward to earlier content as a new latest version', function (): void {
    $doc = TestShareable::create(['title' => 'Rollback']);

    $v1 = Mansio::for($doc)->publishVersion('good-content');
    $v2 = Mansio::for($doc)->publishVersion('broken-content');

    $share = Mansio::for($doc)->share();
    expect($share->serveableVersion()->id)->toBe($v2->id);

    $v3 = Mansio::rollback($doc, toSequence: 1);

    expect($v3->sequence)->toBe(3)
        ->and($v3->content_path)->toBe($v1->content_path)
        ->and($v3->checksum)->toBe($v1->checksum)
        ->and($v3->summary)->toBe('Rolled back to v1')
        ->and($share->fresh()->serveableVersion()->id)->toBe($v3->id);
});

it('throws when rolling back to an unknown sequence', function (): void {
    $doc = TestShareable::create(['title' => 'Bad seq']);
    Mansio::for($doc)->publishVersion('v1');

    expect(fn (): mixed => Mansio::rollback($doc, toSequence: 99))
        ->toThrow(InvalidArgumentException::class);
});
