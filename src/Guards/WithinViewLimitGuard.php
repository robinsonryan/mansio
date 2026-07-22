<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Guards;

use RobinsonRyan\Mansio\Contracts\AccessGuard;
use RobinsonRyan\Mansio\Support\GuardResult;
use RobinsonRyan\Mansio\Support\ShareContext;

/**
 * Denies access once a share has reached its configured maximum view count.
 */
final class WithinViewLimitGuard implements AccessGuard
{
    public function check(ShareContext $context): GuardResult
    {
        $share = $context->share;

        if ($share->max_views !== null && $share->view_count >= $share->max_views) {
            return GuardResult::deny('view_limit');
        }

        return GuardResult::pass();
    }
}
