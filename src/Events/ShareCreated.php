<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Events;

use Illuminate\Foundation\Events\Dispatchable;
use RobinsonRyan\Mansio\Models\Share;

/**
 * Fired when a new public share link is minted for a shareable.
 */
final class ShareCreated
{
    use Dispatchable;

    public function __construct(public readonly Share $share) {}
}
