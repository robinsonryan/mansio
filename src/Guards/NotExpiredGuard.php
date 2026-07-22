<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Guards;

use RobinsonRyan\Mansio\Contracts\AccessGuard;
use RobinsonRyan\Mansio\Support\GuardResult;
use RobinsonRyan\Mansio\Support\ShareContext;

/**
 * Denies access to a share whose expiry has passed.
 */
final class NotExpiredGuard implements AccessGuard
{
    public function check(ShareContext $context): GuardResult
    {
        return $context->share->isExpired()
            ? GuardResult::deny('expired')
            : GuardResult::pass();
    }
}
