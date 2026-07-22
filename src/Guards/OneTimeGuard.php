<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Guards;

use RobinsonRyan\Mansio\Contracts\AccessGuard;
use RobinsonRyan\Mansio\Support\GuardResult;
use RobinsonRyan\Mansio\Support\ShareContext;

/**
 * Burn-after-reading: denies a one-time share once it has been viewed.
 */
final class OneTimeGuard implements AccessGuard
{
    public function check(ShareContext $context): GuardResult
    {
        $share = $context->share;

        if ($share->one_time && $share->view_count >= 1) {
            return GuardResult::deny('consumed');
        }

        return GuardResult::pass();
    }
}
