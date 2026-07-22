<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Events;

use Illuminate\Foundation\Events\Dispatchable;
use RobinsonRyan\Mansio\Models\Share;

/**
 * Fired when a share is observed to have expired.
 */
final class ShareExpired
{
    use Dispatchable;

    public function __construct(public readonly Share $share) {}
}
