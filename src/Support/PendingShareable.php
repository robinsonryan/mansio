<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Support;

use RobinsonRyan\Mansio\Actions\CreateShare;
use RobinsonRyan\Mansio\Actions\PublishVersion;
use RobinsonRyan\Mansio\Contracts\Shareable;
use RobinsonRyan\Mansio\Mansio;
use RobinsonRyan\Mansio\Models\Share;
use RobinsonRyan\Mansio\Models\Version;

/**
 * The fluent builder returned by {@see Mansio::for()}. Binds a
 * shareable to the publish/share actions so callers write
 * `Mansio::for($doc)->publishVersion($bytes)` / `->share($options)`.
 */
final class PendingShareable
{
    public function __construct(
        private readonly Shareable $shareable,
        private readonly PublishVersion $publishVersion,
        private readonly CreateShare $createShare,
    ) {}

    /**
     * @param  string|resource  $bytes
     * @param  array<string, mixed>  $meta
     */
    public function publishVersion(mixed $bytes, array $meta = []): Version
    {
        return $this->publishVersion->handle($this->shareable, $bytes, $meta);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function share(array $options = []): Share
    {
        return $this->createShare->handle($this->shareable, $options);
    }
}
