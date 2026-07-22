<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Events;

use Illuminate\Foundation\Events\Dispatchable;
use RobinsonRyan\Mansio\Models\Share;

/**
 * Fired when a share link is revoked.
 */
final class ShareRevoked
{
    use Dispatchable;

    public function __construct(public readonly Share $share) {}
}
