<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use RobinsonRyan\Mansio\Contracts\ContentStore;
use RuntimeException;

/**
 * Flysystem-backed {@see ContentStore}. Every object path is namespaced under a
 * configured prefix on the given disk, so S3, local, or NAS backends are swappable.
 */
final class FlysystemContentStore implements ContentStore
{
    public function __construct(
        private readonly Filesystem $disk,
        private readonly string $pathPrefix,
    ) {}

    public function put(string $path, mixed $contents): void
    {
        $this->disk->put($this->full($path), $contents);
    }

    public function stream(string $path)
    {
        $stream = $this->disk->readStream($this->full($path));

        if (! is_resource($stream)) {
            throw new RuntimeException("Unable to open a read stream for [{$path}].");
        }

        return $stream;
    }

    public function delete(string $path): void
    {
        $this->disk->delete($this->full($path));
    }

    public function checksum(string $path): string
    {
        return hash('sha256', (string) $this->disk->get($this->full($path)));
    }

    public function exists(string $path): bool
    {
        return $this->disk->exists($this->full($path));
    }

    private function full(string $path): string
    {
        return trim($this->pathPrefix, '/') . '/' . ltrim($path, '/');
    }
}
