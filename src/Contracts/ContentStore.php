<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Contracts;

/**
 * Storage backend abstraction for artifact bytes. The default is Flysystem-backed;
 * S3, local, or NAS implementations are swappable.
 */
interface ContentStore
{
    /**
     * @param  string|resource  $contents
     */
    public function put(string $path, mixed $contents): void;

    /**
     * Open a read stream for streamed / X-Sendfile delivery.
     *
     * @return resource
     */
    public function stream(string $path);

    public function delete(string $path): void;

    /**
     * sha256 checksum of the stored bytes.
     */
    public function checksum(string $path): string;

    /**
     * Whether an object exists at the path.
     */
    public function exists(string $path): bool;
}
