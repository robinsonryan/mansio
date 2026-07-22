<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Actions;

use InvalidArgumentException;
use RobinsonRyan\Mansio\Contracts\Shareable;
use RobinsonRyan\Mansio\Events\VersionPublished;
use RobinsonRyan\Mansio\Models\Version;

/**
 * Repoints "current" to an earlier version by publishing a NEW latest version whose
 * bytes equal the target sequence's. Every outstanding link then serves the
 * rolled-back content, and the changelog records the rollback. History stays
 * append-only — nothing is mutated or deleted.
 */
final class RollbackVersion
{
    public function handle(Shareable $shareable, int $toSequence): Version
    {
        /** @var class-string<Version> $versionClass */
        $versionClass = config('mansio.models.version', Version::class);

        /** @var Version|null $target */
        $target = $versionClass::query()
            ->where('shareable_type', $shareable->getMorphClass())
            ->where('shareable_id', $shareable->getKey())
            ->where('sequence', $toSequence)
            ->first();

        if ($target === null) {
            throw new InvalidArgumentException(
                "mansio: no version with sequence {$toSequence} for this shareable."
            );
        }

        $nextSequence = (int) $versionClass::query()
            ->where('shareable_type', $shareable->getMorphClass())
            ->where('shareable_id', $shareable->getKey())
            ->max('sequence') + 1;

        /** @var Version $version */
        $version = $versionClass::query()->create([
            'shareable_type' => $shareable->getMorphClass(),
            'shareable_id' => $shareable->getKey(),
            'sequence' => $nextSequence,
            'content_path' => $target->content_path,
            'mime' => $target->mime,
            'size_bytes' => $target->size_bytes,
            'checksum' => $target->checksum,
            'source_ref' => $target->source_ref,
            'summary' => "Rolled back to v{$toSequence}",
            'published_by' => null,
            'published_by_type' => null,
            'published_at' => now(),
        ]);

        event(new VersionPublished($version));

        return $version;
    }
}
