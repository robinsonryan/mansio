<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Contracts\ContentStore;
use RobinsonRyan\Mansio\Storage\FlysystemContentStore;

/**
 * R17 — the storage backend is a swappable interface; the default Flysystem store
 * round-trips bytes and derives a sha256 checksum.
 */
it('round-trips put / stream / checksum / exists / delete', function (): void {
    /** @var ContentStore $store */
    $store = app(ContentStore::class);

    expect($store)->toBeInstanceOf(FlysystemContentStore::class);

    $path = 'unit/' . uniqid('blob-', true);
    $bytes = 'THE-QUICK-BROWN-FOX';

    expect($store->exists($path))->toBeFalse();

    $store->put($path, $bytes);

    expect($store->exists($path))->toBeTrue()
        ->and($store->checksum($path))->toBe(hash('sha256', $bytes));

    $stream = $store->stream($path);
    expect(is_resource($stream))->toBeTrue();
    $read = stream_get_contents($stream);
    fclose($stream);

    expect($read)->toBe($bytes);

    $store->delete($path);

    expect($store->exists($path))->toBeFalse();
});

it('checksums are content-addressed (sha256 of the exact bytes)', function (): void {
    /** @var ContentStore $store */
    $store = app(ContentStore::class);

    $a = 'unit/' . uniqid('a-', true);
    $b = 'unit/' . uniqid('b-', true);

    $store->put($a, 'identical-bytes');
    $store->put($b, 'identical-bytes');

    expect($store->checksum($a))
        ->toBe($store->checksum($b))
        ->toBe(hash('sha256', 'identical-bytes'));

    $store->delete($a);
    $store->delete($b);
});
