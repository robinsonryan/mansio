<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Events;

use Illuminate\Foundation\Events\Dispatchable;
use RobinsonRyan\Mansio\Models\Share;
use RobinsonRyan\Mansio\Models\ShareEvent;

/**
 * Fired when a share's artifact is downloaded.
 */
final class ShareDownloaded
{
    use Dispatchable;

    public function __construct(
        public readonly Share $share,
        public readonly ShareEvent $event,
    ) {}
}
