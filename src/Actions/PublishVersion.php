<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Actions;

use RobinsonRyan\Mansio\Contracts\ContentStore;
use RobinsonRyan\Mansio\Contracts\Shareable;
use RobinsonRyan\Mansio\Events\VersionPublished;
use RobinsonRyan\Mansio\Models\Version;

/**
 * Publishes a new immutable content version for a shareable: writes the bytes to the
 * {@see ContentStore}, records the metadata (mime, size, checksum, source ref,
 * summary, publisher) and assigns the next sequence in the shareable's history.
 */
final class PublishVersion
{
    public function __construct(private readonly ContentStore $store) {}

    /**
     * @param  string|resource  $bytes
     * @param  array<string, mixed>  $meta
     */
    public function handle(Shareable $shareable, mixed $bytes, array $meta = []): Version
    {
        /** @var class-string<Version> $versionClass */
        $versionClass = config('mansio.models.version', Version::class);

        $sequence = (int) $versionClass::query()
            ->where('shareable_type', $shareable->getMorphClass())
            ->where('shareable_id', $shareable->getKey())
            ->max('sequence') + 1;

        $path = $this->contentPath($shareable, $sequence);

        $size = is_resource($bytes)
            ? (int) (fstat($bytes)['size'] ?? 0)
            : strlen((string) $bytes);

        $this->store->put($path, $bytes);

        $mime = $meta['mime'] ?? $shareable->mansioDefaultMime();

        /** @var Version $version */
        $version = $versionClass::query()->create([
            'shareable_type' => $shareable->getMorphClass(),
            'shareable_id' => $shareable->getKey(),
            'sequence' => $sequence,
            'content_path' => $path,
            'mime' => $mime,
            'size_bytes' => $size,
            'checksum' => $this->store->checksum($path),
            'source_ref' => $meta['source_ref'] ?? null,
            'summary' => $meta['summary'] ?? null,
            'published_by' => $meta['published_by'] ?? null,
            'published_by_type' => $meta['published_by_type'] ?? null,
            'published_at' => now(),
        ]);

        event(new VersionPublished($version));

        return $version;
    }

    private function contentPath(Shareable $shareable, int $sequence): string
    {
        $type = (string) preg_replace('/[^A-Za-z0-9]+/', '-', $shareable->getMorphClass());
        $type = trim($type, '-');

        return $type . '/' . $shareable->getKey() . '/' . $sequence;
    }
}
