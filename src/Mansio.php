<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio;

use Illuminate\Contracts\Container\Container;
use RobinsonRyan\Mansio\Actions\CreateShare;
use RobinsonRyan\Mansio\Actions\PublishVersion;
use RobinsonRyan\Mansio\Actions\ResolveShare;
use RobinsonRyan\Mansio\Actions\RevokeShare;
use RobinsonRyan\Mansio\Actions\RollbackVersion;
use RobinsonRyan\Mansio\Contracts\Shareable;
use RobinsonRyan\Mansio\Models\Share;
use RobinsonRyan\Mansio\Models\Version;
use RobinsonRyan\Mansio\Support\PendingShareable;
use RobinsonRyan\Mansio\Support\ShareContext;

/**
 * The primary PHP API — the seam CI and the consuming app both call. HTTP endpoints
 * are thin wrappers over this. Every operation delegates to a container-resolved
 * action so behaviour stays swappable and testable in isolation.
 */
final class Mansio
{
    public function __construct(private readonly Container $container) {}

    /**
     * Begin a fluent publish/share operation against a shareable.
     */
    public function for(Shareable $shareable): PendingShareable
    {
        return new PendingShareable(
            $shareable,
            $this->container->make(PublishVersion::class),
            $this->container->make(CreateShare::class),
        );
    }

    /**
     * Resolve a token to a servable share, running the guard pipeline.
     *
     * @param  ShareContext|array<string, mixed>  $context
     */
    public function resolve(string $token, ShareContext|array $context = []): Share
    {
        return $this->container->make(ResolveShare::class)->handle($token, $context);
    }

    /**
     * Revoke a share; subsequent resolution 404s.
     */
    public function revoke(Share $share): Share
    {
        return $this->container->make(RevokeShare::class)->handle($share);
    }

    /**
     * Repoint "current" to an earlier version by publishing a copy as the new latest.
     */
    public function rollback(Shareable $shareable, int $toSequence): Version
    {
        return $this->container->make(RollbackVersion::class)->handle($shareable, $toSequence);
    }
}
