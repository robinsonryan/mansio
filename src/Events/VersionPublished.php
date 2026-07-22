<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Events;

use Illuminate\Foundation\Events\Dispatchable;
use RobinsonRyan\Mansio\Models\Version;

/**
 * Fired when a new immutable version is published for a shareable.
 */
final class VersionPublished
{
    use Dispatchable;

    public function __construct(public readonly Version $version) {}
}
